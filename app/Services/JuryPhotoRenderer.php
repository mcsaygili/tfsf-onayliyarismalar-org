<?php

namespace App\Services;

use Intervention\Image\ImageManager;

class JuryPhotoRenderer
{
    public function render(string $bytes): string
    {
        $image = ImageManager::imagick()->read($bytes);
        $image->scaleDown(width: 2400, height: 2400);

        return (string) $image->toJpeg(quality: 90, progressive: true, strip: true);
    }
}
