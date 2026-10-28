<?php

namespace Roddy\FirestoreEloquent\FileUpload;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class FirestoreFileUpload
{
    public static function upload(string $name, string $path = 'images'): object
    {
        if (!request()->hasFile($name)) {
            throw new InvalidArgumentException("No uploaded file was found for [{$name}].");
        }

        return self::store(UploadedFile::createFromBase(request()->file($name)), $path);
    }

    public static function uploadLivewire(object $file, string $path = 'images'): object
    {
        if (!method_exists($file, 'getRealPath') || !is_file($file->getRealPath())) {
            throw new InvalidArgumentException('The supplied upload is missing or invalid.');
        }

        return self::store($file, $path);
    }

    private static function store(object $file, string $path): object
    {
        $bucketName = config('firebase.storage_bucket');
        $credentials = config(
            'firebase.projects.' . config('firebase.default', 'app') . '.credentials',
            null
        );

        if (!$bucketName) {
            throw new RuntimeException('GOOGLE_CLOUD_STORAGE_BUCKET is not configured.');
        }
        if (!$credentials) {
            throw new RuntimeException('Firebase/Google application credentials are not configured.');
        }

        $size = (int) $file->getSize();
        $maxSize = (int) config('firebase.upload.max_size', 10 * 1024 * 1024);
        if ($size < 1 || $size > $maxSize) {
            throw new InvalidArgumentException("Upload size must be between 1 and {$maxSize} bytes.");
        }

        $mimeType = (string) $file->getMimeType();
        $allowedMimeTypes = (array) config('firebase.upload.allowed_mime_types', []);
        if ($allowedMimeTypes !== [] && !in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException("Uploads with MIME type [{$mimeType}] are not allowed.");
        }

        $safePath = trim(implode('/', array_filter(array_map(
            fn (string $segment) => Str::slug($segment),
            explode('/', str_replace('\\', '/', $path))
        ))), '/');
        $originalBase = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: '';
        $filename = Str::uuid() . '-' . (Str::slug($originalBase) ?: 'upload') . ($extension ? '.' . $extension : '');
        $objectName = $safePath ? $safePath . '/' . $filename : $filename;
        $downloadToken = (string) Str::uuid();

        $storage = new StorageClient(['keyFilePath' => self::absolutePath($credentials)]);
        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('Unable to open the uploaded file.');
        }

        try {
            $object = $storage->bucket($bucketName)->upload($stream, [
                'name' => $objectName,
                'metadata' => [
                    'contentType' => $mimeType,
                    'metadata' => ['firebaseStorageDownloadTokens' => $downloadToken],
                ],
            ]);
        } finally {
            fclose($stream);
        }

        $info = $object->info();
        $url = 'https://firebasestorage.googleapis.com/v0/b/' . rawurlencode($bucketName)
            . '/o/' . rawurlencode($objectName);

        return (object) [
            'url' => $url . '?alt=media',
            'downloadUrl' => $url . '?alt=media&token=' . rawurlencode($downloadToken),
            'contentType' => $info['contentType'] ?? $mimeType,
            'downloadTokens' => $downloadToken,
            'size' => $info['size'] ?? $size,
            'updated' => $info['updated'] ?? null,
            'timeCreated' => $info['timeCreated'] ?? null,
            'bucket' => $info['bucket'] ?? $bucketName,
            'etag' => $info['etag'] ?? null,
            'generation' => $info['generation'] ?? null,
            'nameAndPath' => $info['name'] ?? $objectName,
        ];
    }

    private static function absolutePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
