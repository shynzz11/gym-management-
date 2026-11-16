<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait UploadsMedia
{
    /**
     * Upload a single file.
     *
     * @param  string|null  $folder
     * @param  string  $disk
     * @param  string|null  $filename
     * @return string|false
     */
    public function uploadOne(UploadedFile $file, $folder = null, $disk = 'public', $filename = null)
    {
        $name = ! is_null($filename) ? $filename : Str::random(25);

        return $file->storeAs(
            $folder,
            // extension() is guessed from the file contents, not taken from the client-supplied name.
            $name.'.'.$file->extension(),
            $disk
        );
    }

    /**
     * Delete a single file.
     *
     * @param  string  $path
     * @param  string  $disk
     * @return bool
     */
    public function deleteOne($path, $disk = 'public')
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }
}
