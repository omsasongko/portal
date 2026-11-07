<?php

namespace App\Libraries;

/**
 * Minimal, self-contained MaxMind DB (MMDB) reader.
 *
 * Reads the binary search tree and data section of an MMDB file to
 * resolve IPv4 and IPv6 addresses.  No external dependencies required.
 *
 * Uses file-based I/O (fseek/fread) to avoid loading the entire
 * database into memory, keeping usage well within typical PHP limits.
 *
 * Supports MMDB format version 2.0 as documented at:
 * https://maxmind.github.io/MaxMind-DB/
 *
 * @internal Only used by GeoIpLookup. Not part of the public API.
 */
class MmdbReader
{
    /** @var resource */
    private $fh;
    private int    $fileSize;
    private int    $nodeCount;
    private int    $recordSize;
    private int    $nodeByteSize;
    private int    $searchTreeSize;
    private int    $dataSectionStart;
    private int    $ipVersion;

    /** @var array<string, mixed> */
    private array $metadata;

    /** Small read-through cache keyed by absolute file offset. */
    private array $cache = [];

    private const METADATA_MARKER = "\xAB\xCD\xEFMaxMind.com";
    private const DATA_TYPE_POINTER  = 1;
    private const DATA_TYPE_UTF8     = 2;
    private const DATA_TYPE_DOUBLE   = 3;
    private const DATA_TYPE_BYTES    = 4;
    private const DATA_TYPE_UINT16   = 5;
    private const DATA_TYPE_UINT32   = 6;
    private const DATA_TYPE_MAP      = 7;
    private const DATA_TYPE_INT32    = 8;
    private const DATA_TYPE_UINT64   = 9;
    private const DATA_TYPE_UINT128  = 10;
    private const DATA_TYPE_ARRAY    = 11;
    private const DATA_TYPE_BOOLEAN  = 14;
    private const DATA_TYPE_FLOAT    = 15;

    /**
     * Open an MMDB file.
     *
     * @throws \RuntimeException on I/O or format error.
     */
    public function __construct(string $path)
    {
        if (! is_readable($path)) {
            throw new \RuntimeException("Cannot read MMDB file: {$path}");
        }

        $this->fh = fopen($path, 'rb');
        if ($this->fh === false) {
            throw new \RuntimeException("Cannot open MMDB file: {$path}");
        }

        $this->fileSize = (int) filesize($path);
        if ($this->fileSize < 100) {
            throw new \RuntimeException('MMDB file too small');
        }

        $this->loadMetadata();

        $this->nodeCount      = $this->metadata['node_count'];
        $this->recordSize     = $this->metadata['record_size'];
        $this->ipVersion      = $this->metadata['ip_version'];
        $this->nodeByteSize   = (int) ($this->recordSize * 2 / 8);
        $this->searchTreeSize = $this->nodeCount * $this->nodeByteSize;
        // 16-byte data separator follows the search tree.
        $this->dataSectionStart = $this->searchTreeSize + 16;
    }

    public function __destruct()
    {
        if (is_resource($this->fh)) {
            fclose($this->fh);
        }
    }

    /**
     * Look up an IP address and return the associated record array,
     * or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function lookup(string $ip): ?array
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }

        $bits = $this->ipToBits($packed);
        $node = 0;

        foreach ($bits as $bit) {
            if ($node >= $this->nodeCount) {
                break;
            }
            $node = $this->readNode($node, $bit);
        }

        if ($node === $this->nodeCount) {
            return null;
        }

        if ($node > $this->nodeCount) {
            $offset = $node - $this->nodeCount - 16;
            [$result] = $this->decodeData($this->dataSectionStart + $offset);
            return is_array($result) ? $result : null;
        }

        return null;
    }

    /**
     * Read bytes from the file at a specific offset.
     */
    private function readBytes(int $offset, int $length): string
    {
        fseek($this->fh, $offset, SEEK_SET);
        $data = fread($this->fh, $length);
        if ($data === false || strlen($data) < $length) {
            throw new \RuntimeException("Failed to read {$length} bytes at offset {$offset}");
        }
        return $data;
    }

    /**
     * Read a single byte from the file.
     */
    private function readByte(int $offset): int
    {
        return ord($this->readBytes($offset, 1));
    }

    /**
     * Convert a packed IP to an array of bit values (0 or 1).
     */
    private function ipToBits(string $packed): array
    {
        $bits = [];
        foreach (str_split($packed) as $byte) {
            $ord = ord($byte);
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($ord >> $i) & 1;
            }
        }

        if ($this->ipVersion === 6 && count($bits) === 32) {
            $bits = array_merge(array_fill(0, 96, 0), $bits);
        }

        return $bits;
    }

    /**
     * Read one side (left=0, right=1) of a search-tree node.
     */
    private function readNode(int $nodeNumber, int $index): int
    {
        $offset = $nodeNumber * $this->nodeByteSize;

        switch ($this->recordSize) {
            case 24:
                if ($index === 0) {
                    return $this->readUint($offset, 3);
                }
                return $this->readUint($offset + 3, 3);

            case 28:
                if ($index === 0) {
                    $middle = $this->readByte($offset + 3) >> 4;
                    return ($middle << 24) | $this->readUint($offset, 3);
                }
                $middle = $this->readByte($offset + 3) & 0x0F;
                return ($middle << 24) | $this->readUint($offset + 4, 3);

            case 32:
                if ($index === 0) {
                    return $this->readUint($offset, 4);
                }
                return $this->readUint($offset + 4, 4);

            default:
                throw new \RuntimeException("Unsupported record size: {$this->recordSize}");
        }
    }

    /**
     * Read an unsigned integer of 1–4 bytes from the file.
     */
    private function readUint(int $offset, int $length): int
    {
        $bytes = $this->readBytes($offset, $length);
        $val = 0;
        for ($i = 0; $i < $length; $i++) {
            $val = ($val << 8) | ord($bytes[$i]);
        }
        return $val;
    }

    /**
     * Decode a data-section value at the given absolute file offset.
     *
     * @return array{0: mixed, 1: int}  [decodedValue, newOffset]
     */
    private function decodeData(int $offset): array
    {
        $startOffset = $offset;

        if (isset($this->cache[$startOffset])) {
            return $this->cache[$startOffset];
        }

        $ctrlByte = $this->readByte($offset);
        $type     = $ctrlByte >> 5;
        $offset++;

        if ($type === 0) {
            $type = $this->readByte($offset) + 7;
            $offset++;
        }

        if ($type === self::DATA_TYPE_POINTER) {
            [$pointer, $offset] = $this->decodePointer($ctrlByte, $offset);
            $targetOffset       = $this->dataSectionStart + $pointer;
            [$value]            = $this->decodeData($targetOffset);
            $result             = [$value, $offset];
            $this->cache[$startOffset] = $result;
            return $result;
        }

        $size = $ctrlByte & 0x1F;

        if ($size === 29) {
            $size = 29 + $this->readByte($offset);
            $offset++;
        } elseif ($size === 30) {
            $size = 285 + $this->readUint($offset, 2);
            $offset += 2;
        } elseif ($size === 31) {
            $size = 65821 + $this->readUint($offset, 3);
            $offset += 3;
        }

        $result = $this->decodeByType($type, $offset, $size);
        $this->cache[$startOffset] = $result;
        return $result;
    }

    /**
     * Decode a pointer from the control byte.
     *
     * @return array{0: int, 1: int} [pointerValue, newOffset]
     */
    private function decodePointer(int $ctrlByte, int $offset): array
    {
        $ptrSize = ($ctrlByte >> 3) & 0x03;

        switch ($ptrSize) {
            case 0:
                $ptr = (($ctrlByte & 0x07) << 8) | $this->readByte($offset);
                return [$ptr, $offset + 1];
            case 1:
                $ptr = (($ctrlByte & 0x07) << 16) | $this->readUint($offset, 2);
                return [$ptr + 2048, $offset + 2];
            case 2:
                $ptr = (($ctrlByte & 0x07) << 24) | $this->readUint($offset, 3);
                return [$ptr + 526336, $offset + 3];
            case 3:
                $ptr = $this->readUint($offset, 4);
                return [$ptr, $offset + 4];
            default:
                throw new \RuntimeException('Invalid pointer size');
        }
    }

    /**
     * Decode a typed value from the data section.
     *
     * @return array{0: mixed, 1: int} [value, newOffset]
     */
    private function decodeByType(int $type, int $offset, int $size): array
    {
        switch ($type) {
            case self::DATA_TYPE_MAP:
                $map = [];
                for ($i = 0; $i < $size; $i++) {
                    [$key, $offset]   = $this->decodeData($offset);
                    [$value, $offset] = $this->decodeData($offset);
                    $map[(string) $key] = $value;
                }
                return [$map, $offset];

            case self::DATA_TYPE_ARRAY:
                $arr = [];
                for ($i = 0; $i < $size; $i++) {
                    [$value, $offset] = $this->decodeData($offset);
                    $arr[] = $value;
                }
                return [$arr, $offset];

            case self::DATA_TYPE_UTF8:
                return [$this->readBytes($offset, $size), $offset + $size];

            case self::DATA_TYPE_BYTES:
                return [$this->readBytes($offset, $size), $offset + $size];

            case self::DATA_TYPE_DOUBLE:
                $bytes = $this->readBytes($offset, 8);
                return [unpack('E', $bytes)[1], $offset + 8];

            case self::DATA_TYPE_FLOAT:
                $bytes = $this->readBytes($offset, 4);
                return [unpack('G', $bytes)[1], $offset + 4];

            case self::DATA_TYPE_UINT16:
            case self::DATA_TYPE_UINT32:
                if ($size === 0) {
                    return [0, $offset];
                }
                $bytes = $this->readBytes($offset, $size);
                $val = 0;
                for ($i = 0; $i < $size; $i++) {
                    $val = ($val << 8) | ord($bytes[$i]);
                }
                return [$val, $offset + $size];

            case self::DATA_TYPE_UINT64:
            case self::DATA_TYPE_UINT128:
                if ($size === 0) {
                    return ['0', $offset];
                }
                $bytes = $this->readBytes($offset, $size);
                $hex = '';
                for ($i = 0; $i < $size; $i++) {
                    $hex .= sprintf('%02x', ord($bytes[$i]));
                }
                return [ltrim($hex, '0') ?: '0', $offset + $size];

            case self::DATA_TYPE_INT32:
                if ($size === 0) {
                    return [0, $offset];
                }
                $bytes = $this->readBytes($offset, $size);
                $val = 0;
                for ($i = 0; $i < $size; $i++) {
                    $val = ($val << 8) | ord($bytes[$i]);
                }
                if ($size === 4 && ($val & 0x80000000)) {
                    $val -= 0x100000000;
                }
                return [$val, $offset + $size];

            case self::DATA_TYPE_BOOLEAN:
                return [$size !== 0, $offset];

            default:
                if ($size > 0) {
                    return [null, $offset + $size];
                }
                return [null, $offset];
        }
    }

    /**
     * Locate and parse the metadata block at the end of the file.
     */
    private function loadMetadata(): void
    {
        // Read the last 128KB to find the metadata marker.
        $searchLen = min($this->fileSize, 131072);
        $tail = $this->readBytes($this->fileSize - $searchLen, $searchLen);

        $markerLen = strlen(self::METADATA_MARKER);
        $pos = strrpos($tail, self::METADATA_MARKER);
        if ($pos === false) {
            throw new \RuntimeException('MMDB metadata marker not found');
        }

        $metaStart = ($this->fileSize - $searchLen) + $pos + $markerLen;
        [$meta] = $this->decodeData($metaStart);

        if (! is_array($meta) || empty($meta['node_count']) || empty($meta['record_size'])) {
            throw new \RuntimeException('Invalid MMDB metadata');
        }

        $this->metadata = $meta;
    }
}
