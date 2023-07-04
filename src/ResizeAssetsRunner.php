<?php

namespace Sunnysideup\ResizeAssets;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Imagick;

class ResizeAssetsRunner
{
    protected static $useImagick = false;
    protected static $useGd = false;

    public function set_imagegick_as_converter()
    {
        self::$useImagick = true;
    }

    public function set_gd_as_converter()
    {
        self::$useGd = true;
    }

    public static function run_dir(string $dir, string $img, int $maxWidth, int $maxHeight, ?bool $dryRun = true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif'])) {
                self::run_one($file->getPathname(), $maxWidth, $maxHeight, $dryRun);
            }
        }
    }

    public static function run_one(string $img, int $maxWidth, int $maxHeight, ?bool $dryRun = true)
    {
        echo "---".PHP_EOL;
        self::getImageResizerLib();
        list($width, $height) = getimagesize($img);
        if ($width <= $maxWidth && $height <= $maxHeight) {
            echo "Skipping $img ({$width}x{$height})".PHP_EOL;
            return;
        }
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;

        if ($dryRun) {
            echo "Dry run: Would resize $img ({$width}x{$height}) to ($newWidth x $newHeight)".PHP_EOL;
            return;
        }

        echo "Resizing $img".PHP_EOL;


        if(self::$useImagick) {
            $image = new Imagick($img);
            $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            $image->writeImage($img);
            $image->clear();
            $image->destroy();
        } elseif(self::$useGd) {

            // Create a new true color image
            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // Load the image
            switch (strtolower(pathinfo($img, PATHINFO_EXTENSION))) {
                case 'jpg':
                case 'jpeg':
                    $sourceImage = imagecreatefromjpeg($img);
                    break;
                case 'png':
                    $sourceImage = imagecreatefrompng($img);
                    break;
                case 'gif':
                    $sourceImage = imagecreatefromgif($img);
                    break;
            }
            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Save the image
            switch (strtolower(pathinfo($img, PATHINFO_EXTENSION))) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($newImage, $img);
                    break;
                case 'png':
                    imagepng($newImage, $img);
                    break;
                case 'gif':
                    imagegif($newImage, $img);
                    break;
            }

            // Free up memory
            imagedestroy($sourceImage);
            imagedestroy($newImage);
        } else {

        }
        // Copy and resize part of an image with resampling

    }

    protected static function getImageResizerLib()
    {
        if(self::$useImagick || self::$useGd) {
            return;
        }
        if (extension_loaded('imagick')) {
            self::$useImagick = true;
            self::$useGd = false;
        } elseif (extension_loaded('gd')) {
            self::$useGd = true;
            self::$useImagick = false;
        } else {
            exit("Error: Neither Imagick nor GD is installed.\n");
        }
    }
}
