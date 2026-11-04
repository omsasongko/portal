<?php

namespace App\Controllers\Admin\Traits;

use App\Models\MediaModel;

/**
 * Shared image upload handling for admin forms (extension + MIME allowlist,
 * content validation, storage under public/assets/portal/uploads/media,
 * registration in the shared media library).
 */
trait HandlesImageUpload
{
    protected array $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    protected array $imageMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    protected int $imageMinSize = 51200;     // 50 KB
    protected int $imageMaxSize = 10485760;  // 10 MB

    /**
     * Returns an Indonesian error message when an uploaded file is invalid,
     * or null when there is no upload / the upload is acceptable.
     */
    protected function validateImageUpload(string $field): ?string
    {
        $file = $this->request->getFile($field);

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }

        $ext = strtolower($file->getExtension());
        $clientExt = strtolower($file->getClientExtension());
        if (! in_array($ext, $this->imageExtensions, true) || ! in_array($clientExt, $this->imageExtensions, true)) {
            return 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.';
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, $this->imageMimes, true)) {
            return 'File bukan gambar yang valid.';
        }

        $size = $file->getSize();
        if ($size < $this->imageMinSize) {
            return 'Ukuran file terlalu kecil. Minimum 50 KB.';
        }

        if ($size > $this->imageMaxSize) {
            return 'Ukuran file terlalu besar. Maksimum 10 MB.';
        }

        $imageInfo = @getimagesize($file->getTempName());
        if ($imageInfo === false) {
            return 'File bukan gambar yang valid (konten tidak dikenali).';
        }

        return null;
    }

    /**
     * Move a valid upload into the media library, or fall back to the
     * library-selected path / existing stored path.
     */
    protected function resolveImagePath(string $field, ?string $selectedPath): string
    {
        $file = $this->request->getFile($field);

        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $ext = strtolower($file->getExtension());
            $clientExt = strtolower($file->getClientExtension());
            if (! in_array($ext, $this->imageExtensions, true) || ! in_array($clientExt, $this->imageExtensions, true)) {
                return (string) ($selectedPath ?? '');
            }

            $mime = $file->getMimeType();
            if (! in_array($mime, $this->imageMimes, true)) {
                return (string) ($selectedPath ?? '');
            }

            if ($file->getSize() < $this->imageMinSize || $file->getSize() > $this->imageMaxSize) {
                return (string) ($selectedPath ?? '');
            }

            if (@getimagesize($file->getTempName()) === false) {
                return (string) ($selectedPath ?? '');
            }

            $uploadDir = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'portal' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'media';
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newName = $file->getRandomName();
            if (! $file->move($uploadDir, $newName)) {
                return (string) ($selectedPath ?? '');
            }

            $relativePath = 'assets/portal/uploads/media/' . $newName;

            (new MediaModel())->insert([
                'title'     => $file->getClientName(),
                'filename'  => $relativePath,
                'mime_type' => $mime,
            ]);

            return $relativePath;
        }

        return (string) ($selectedPath ?? '');
    }
}
