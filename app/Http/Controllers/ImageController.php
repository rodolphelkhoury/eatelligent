<?php

namespace App\Http\Controllers;

use App\Actions\UploadImage;
use App\Http\Requests\Image\UploadImageRequest;

class ImageController extends Controller
{
    /**
     * Upload an image using the UploadImage action (dev endpoint).
     */
    public function createImage(UploadImageRequest $request, UploadImage $uploader)
    {
        try {
            $image = $uploader->execute(
                $request->file('image'),
            );

            return response()->json(['image' => $image], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Upload failed', 'error' => $e->getMessage()], 500);
        }
    }
}
