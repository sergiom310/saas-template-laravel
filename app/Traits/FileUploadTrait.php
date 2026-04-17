<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

trait FileUploadTrait
{

    public static $image_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    public static $audio_ext = ['mp3', 'wma'];
    public static $video_ext = ['mp4', 'wmv', 'avi', 'mpeg'];
    public static $document_ext = ['doc', 'docx', 'pdf', 'txt', 'xls', 'xlsx'];

    /**
     * Get all extensions
     * @return array Extensions of all file types
     */
    public static function allExtensions()
    {
        return array_merge(
            self::$image_ext,
            self::$audio_ext,
            self::$video_ext,
            self::$document_ext
        );
    }

    /**
     * Upload a file to the specified path.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $path
     * @param array $allowedExtensions
     * @return string|false
     * @throws Exception
     */
    public function uploadFile($file, $path = 'uploads')
    {

        $allowedExtensions = $this->allExtensions();

        // Get file extension
        $extension = strtolower($file->getClientOriginalExtension());

        // Validate file extension
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception("File extension .$extension is not allowed.");
        }

        // Generate a unique file name
        $fileName = Str::random(20) . '.' . $extension;

        // Store the file
        $filePath = $file->storeAs($path, $fileName);

        if (!$filePath) {
            throw new Exception("Failed to upload file.");
        }

        return str_replace('public/', 'storage/', $filePath);
    }

    public function fileExists($path)
    {
        return Storage::disk('local')->exists($path);
    }

    /**
     * Delete a file from storage.
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteFile($path)
    {
        if ($this->fileExists($path)) {
            return Storage::disk('local')->delete($path);
        }
        return false;
    }
}
