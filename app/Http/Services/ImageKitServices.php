<?php

namespace App\Http\Services;

use ImageKit\ImageKit;

class ImageKitServices{
    protected $imageKit;

    public function __construct()
    {
        $this->imageKit = new ImageKit(
            config('imagekit.publicKey'),
            config('imagekit.privateKey'),
            config('imagekit.urlEndpoint'),
        );
    }

   public function ImageKitUpload($rawBytes, $fileName)
{
    $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($rawBytes); // e.g. image/jpeg
    $b64  = base64_encode($rawBytes);

    $response = $this->imageKit->upload([
        'file'     => "data:{$mime};base64,{$b64}",
        'fileName' => $fileName,
        'folder'   => '/bambu'
    ]);

    // \Log::info('IK response', [
    //     'url'  => $response->result->url  ?? null,
    //     'size' => $response->result->size ?? null,
    // ]);

    return $response;
}

    public function ImageKitUpdate($oldFileId, $rawBytes, string $fileName)
    {
        // 1. Remove previous asset if requested
        if ($oldFileId) {
            $this->ImageKitDelete($oldFileId);
        }

        // 2. Upload new asset
        return $this->ImageKitUpload($rawBytes, $fileName);
    }

    public function ImageKitDelete($fileId){
        return $this->imageKit->deleteFile($fileId);
    }
}