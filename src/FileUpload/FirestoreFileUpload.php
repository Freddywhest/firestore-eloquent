<?php
namespace Roddy\FirestoreEloquent\FileUpload;

use Illuminate\Http\UploadedFile;
use Google\Cloud\Storage\StorageClient;


class FirestoreFileUpload
{
    public static function upload(string $name, $path = 'images')
    {
        if(!env('GOOGLE_CLOUD_STORAGE_BUCKET')){
            return throw new \Exception('Please set GOOGLE_CLOUD_STORAGE_BUCKET in your .env file');
        }

        if(!env('GOOGLE_APPLICATION_CREDENTIALS')){
            return throw new \Exception('Please set GOOGLE_APPLICATION_CREDENTIALS in your .env file');
        }

        if(request()->hasFile($name)){
            $file = UploadedFile::createFromBase(request()->file($name));

            $storage = new StorageClient([
                'keyFilePath' => base_path().'/'.env('GOOGLE_APPLICATION_CREDENTIALS'),
            ]);

            $bucket = $storage->bucket(env('GOOGLE_CLOUD_STORAGE_BUCKET'));

             // Upload the file to Google Cloud Storage
            $objectName = $path ? $path.'/'. time().'-'.$file->getClientOriginalName() :  time().'-'.$file->getClientOriginalName();
            $bucket->upload(
                $file->get(),
                ['name' => $objectName]
            );

            // Get the public URL of the uploaded file
            $url = 'https://firebasestorage.googleapis.com/v0/b/' . env('GOOGLE_CLOUD_STORAGE_BUCKET') . '/o/' . urlencode($objectName);

            $payload = file_get_contents($url);

            return (object) [
                'url' => $url.'?alt=media',
                'downloadUrl' => $url.'?alt=media&token='.json_decode($payload)->downloadTokens,
                'contentType' => json_decode($payload)->contentType,
                'downloadTokens' => json_decode($payload)->downloadTokens,
                'size' => json_decode($payload)->size,
                'updated' => json_decode($payload)->updated,
                'timeCreated' => json_decode($payload)->timeCreated,
                'bucket' => json_decode($payload)->bucket,
                'etag' => json_decode($payload)->etag,
                'generation' => json_decode($payload)->generation,
                'nameAndPath' => json_decode($payload)->name,
            ];
        }else{
            $trace = debug_backtrace();
            trigger_error('Invalid file name provided ["'.$name.'"] or no file was uploaded with the name provided in the request object.'.
            ' Trace => File : '. $trace[0]['file'] . ', Line : '. $trace[0]['line'], E_USER_ERROR);
        }
    }

    public static function uploadLivewire(object $file, $path = 'images')
    {
        if(!env('GOOGLE_CLOUD_STORAGE_BUCKET')){
            return throw new \Exception('Please set GOOGLE_CLOUD_STORAGE_BUCKET in your .env file');
        }

        if(!env('GOOGLE_APPLICATION_CREDENTIALS')){
            return throw new \Exception('Please set GOOGLE_APPLICATION_CREDENTIALS in your .env file');
        }

        if(file_exists($file->getRealPath())){
            $storage = new StorageClient([
                'keyFilePath' => base_path().'/'.env('GOOGLE_APPLICATION_CREDENTIALS'),
            ]);

            $bucket = $storage->bucket(env('GOOGLE_CLOUD_STORAGE_BUCKET'));

             // Upload the file to Google Cloud Storage
            $objectName = $path ? $path.'/'. time().'-'.$file->getClientOriginalName() :  time().'-'.$file->getClientOriginalName();
            $bucket->upload(
                $file->get(),
                ['name' => $objectName]
            );

            // Get the public URL of the uploaded file
            $url = 'https://firebasestorage.googleapis.com/v0/b/' . env('GOOGLE_CLOUD_STORAGE_BUCKET') . '/o/' . urlencode($objectName);

            $payload = file_get_contents($url);

            return (object) [
                'url' => $url.'?alt=media',
                'downloadUrl' => $url.'?alt=media&token='.json_decode($payload)->downloadTokens,
                'contentType' => json_decode($payload)->contentType,
                'downloadTokens' => json_decode($payload)->downloadTokens,
                'size' => json_decode($payload)->size,
                'updated' => json_decode($payload)->updated,
                'timeCreated' => json_decode($payload)->timeCreated,
                'bucket' => json_decode($payload)->bucket,
                'etag' => json_decode($payload)->etag,
                'generation' => json_decode($payload)->generation,
                'nameAndPath' => json_decode($payload)->name,
            ];
        }else{
            $trace = debug_backtrace();
            trigger_error('Attempted to upload a file that does not exist or an empty file. Trace => File : '. $trace[0]['file'] . ', Line : '. $trace[0]['line'], E_USER_ERROR);
        }
    }
}
