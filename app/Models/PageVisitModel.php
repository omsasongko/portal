<?php

namespace App\Models;

use CodeIgniter\Model;

class PageVisitModel extends Model
{
    protected $table         = 'page_visits';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'page_type', 'page_id', 'page_path',
        'ip_address', 'user_agent',
        'browser', 'os', 'device_type', 'is_bot',
        'country', 'region', 'city',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    // ------------------------------------------------------------------
    // Timezone handling
    //
    // Timestamps are stored in UTC (PHP appTimezone = 'UTC').
    // The analytics dashboard displays and filters in Asia/Jakarta (WIB,
    // UTC+7).  All conversions happen at query/display time — stored
    // values are never modified.
    // ------------------------------------------------------------------

    /** Display timezone offset applied via CONVERT_TZ / DateTimeZone. */
    private const TZ_STORED = '+00:00';
    private const TZ_DISPLAY = '+07:00';
    private const TZ_PHP = 'Asia/Jakarta';

    /**
     * Convert a WIB calendar-date range into UTC boundaries for WHERE
     * clauses.  The admin picks WIB dates (e.g. 2026-11-07); midnight
     * WIB = 2026-11-06 17:00:00 UTC, end of day WIB = 2026-11-07
     * 16:59:59 UTC.
     *
     * @return array{string, string} [utcStart, utcEnd]
     */
    private function wibRangeToUtc(string $wibFrom, string $wibTo): array
    {
        $tz = new \DateTimeZone(self::TZ_PHP);
        $utc = new \DateTimeZone('UTC');

        $start = new \DateTime($wibFrom . ' 00:00:00', $tz);
        $start->setTimezone($utc);

        $end = new \DateTime($wibTo . ' 23:59:59', $tz);
        $end->setTimezone($utc);

        return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
    }

    /**
     * Apply the WIB date-range filter to the current builder.
     *
     * @return $this
     */
    private function applyRange(string $wibFrom, string $wibTo): self
    {
        [$utcStart, $utcEnd] = $this->wibRangeToUtc($wibFrom, $wibTo);

        return $this->where('created_at >=', $utcStart)
                    ->where('created_at <=', $utcEnd);
    }

    /**
     * Total visit count in a WIB date range (excludes bots).
     */
    public function totalVisits(string $from, string $to): int
    {
        $this->where('is_bot', 0);
        $this->applyRange($from, $to);

        return (int) $this->countAllResults();
    }

    /**
     * Most visited pages in a WIB date range (excludes bots).
     *
     * @return array<array{page_type:string, page_id:int, page_path:string, visits:int}>
     */
    public function topPages(string $from, string $to, int $limit = 10): array
    {
        [$utcStart, $utcEnd] = $this->wibRangeToUtc($from, $to);

        return $this->select('page_type, page_id, page_path, COUNT(*) AS visits')
            ->where('is_bot', 0)
            ->where('created_at >=', $utcStart)
            ->where('created_at <=', $utcEnd)
            ->groupBy('page_type, page_id, page_path')
            ->orderBy('visits', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Browser breakdown in a WIB date range (excludes bots).
     *
     * @return array<array{browser:string, visits:int}>
     */
    public function browserBreakdown(string $from, string $to): array
    {
        [$utcStart, $utcEnd] = $this->wibRangeToUtc($from, $to);

        return $this->select("browser, COUNT(*) AS visits")
            ->where('is_bot', 0)
            ->where('created_at >=', $utcStart)
            ->where('created_at <=', $utcEnd)
            ->groupBy('browser')
            ->orderBy('visits', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Device-type breakdown in a WIB date range (excludes bots).
     *
     * @return array<array{device_type:string, visits:int}>
     */
    public function deviceBreakdown(string $from, string $to): array
    {
        [$utcStart, $utcEnd] = $this->wibRangeToUtc($from, $to);

        return $this->select("device_type, COUNT(*) AS visits")
            ->where('is_bot', 0)
            ->where('created_at >=', $utcStart)
            ->where('created_at <=', $utcEnd)
            ->groupBy('device_type')
            ->orderBy('visits', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Recent visits (includes bot flag) newest first.
     * The created_at value is returned converted to WIB for display.
     *
     * @return array<array{id:int, page_type:string, page_path:string, ip_address:string, browser:string, os:string, device_type:string, is_bot:int, country:?string, region:?string, city:?string, created_at:string}>
     */
    public function recentVisits(string $from, string $to, int $limit = 50): array
    {
        [$utcStart, $utcEnd] = $this->wibRangeToUtc($from, $to);

        return $this->select("id, page_type, page_path, ip_address, browser, os, device_type, is_bot, country, region, city, CONVERT_TZ(created_at, '" . self::TZ_STORED . "', '" . self::TZ_DISPLAY . "') AS created_at")
            ->where('created_at >=', $utcStart)
            ->where('created_at <=', $utcEnd)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * IPs with the highest visit counts in a WIB date range.
     * Used as a review aid — not for automatic blocking.
     * first_visit and last_visit are returned in WIB.
     *
     * @return array<array{ip_address:string, visits:int, distinct_pages:int, first_visit:string, last_visit:string}>
     */
    public function frequentIps(string $from, string $to, int $minVisits = 20, int $limit = 20): array
    {
        [$utcStart, $utcEnd] = $this->wibRangeToUtc($from, $to);

        return $this->select("ip_address, COUNT(*) AS visits, COUNT(DISTINCT page_path) AS distinct_pages, CONVERT_TZ(MIN(created_at), '" . self::TZ_STORED . "', '" . self::TZ_DISPLAY . "') AS first_visit, CONVERT_TZ(MAX(created_at), '" . self::TZ_STORED . "', '" . self::TZ_DISPLAY . "') AS last_visit")
            ->where('is_bot', 0)
            ->where('created_at >=', $utcStart)
            ->where('created_at <=', $utcEnd)
            ->groupBy('ip_address')
            ->having('visits >=', $minVisits)
            ->orderBy('visits', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Daily visit counts in a WIB date range (for chart data).
     * Days are grouped by the WIB calendar date.
     *
     * @return array<array{date:string, visits:int}>
     */
    public function dailyCounts(string $from, string $to): array
    {
        [$utcStart, $utcEnd] = $this->wibRangeToUtc($from, $to);

        return $this->select("DATE(CONVERT_TZ(created_at, '" . self::TZ_STORED . "', '" . self::TZ_DISPLAY . "')) AS date, COUNT(*) AS visits")
            ->where('is_bot', 0)
            ->where('created_at >=', $utcStart)
            ->where('created_at <=', $utcEnd)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Delete records older than the given number of days.
     *
     * @return int Number of deleted rows.
     */
    public function purgeOlderThan(int $days): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $this->where('created_at <', $cutoff)->delete();

        return $this->db->affectedRows();
    }
}
