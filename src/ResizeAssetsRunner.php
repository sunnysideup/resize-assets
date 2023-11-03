<?php

namespace Sunnysideup\ResizeAssets;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Imagick;

class ResizeAssetsRunner
{
    protected static $useImagick = false;
    protected static $useGd = false;
    protected static $patterns_to_skip = [];
    protected static $max_file_size_in_mb = 2;

    public static function set_imagick_as_converter()
    {
        self::$useImagick = true;
        self::$useGd = false;
    }

    public static function set_gd_as_converter()
    {
        self::$useGd = true;
        self::$useImagick = false;
    }

    public static function set_max_file_size_in_mb(?int $max_file_size_in_mb = 2)
    {
        self::$max_file_size_in_mb = $max_file_size_in_mb = $max_file_size_in_mb;
    }

    public static function set_gd_or_imagick_as_converter()
    {
        self::getImageResizerLib();
    }

    /**
     * e.g. providing `['___']` will exclude all files with `___` in them.
     */
    public static function patterns_to_skip(array $array)
    {
        self::$patterns_to_skip = $array;
    }

    public static function run_dir(string $dir, int $maxWidth, int $maxHeight, ?bool $dryRun = true)
    {
        self::set_gd_or_imagick_as_converter();

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif'])) {
                foreach(self::$patterns_to_skip as $pattern) {
                    if(strpos($file, $pattern) !== false) {
                        continue 2;
                    }
                }
                self::run_one($file->getPathname(), $maxWidth, $maxHeight, $dryRun);
                $sizeCheck = self::isFileSizeGreaterThan($file->getPathname());
                if($sizeCheck) {
                    list($width, $height) = getimagesize($file->getPathname());
                    $ratio = $width / $height;
                    self::run_one($file->getPathname(), round($width - (1 * $sizeCheck * $ratio)), round($height - (1 * $sizeCheck * $ratio)), $dryRun, true);
                    echo 'ERROR! ' . $file . ' is still ' . $sizeCheck . '% too big!' . PHP_EOL;
                }
            }
        }
    }

    public static function run_one(string $img, int $maxWidth, int $maxHeight, ?bool $dryRun = true, ?bool $force = false)
    {
        list($width, $height) = getimagesize($img);
        if ($width <= $maxWidth && $height <= $maxHeight && !$force) {
            if($dryRun) {
                echo "-- skipping $img ({$width}x{$height})" . PHP_EOL;
            }
            return;
        }
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        if ($dryRun) {
            echo "Dry run: Would resize $img ({$width}x{$height}) to ($newWidth x $newHeight)" . PHP_EOL;
            return;
        }

        echo "Resizing $img" . PHP_EOL;


        if(self::$useImagick) {
            /** @var \Imagick $Image */
            $image = new \Imagick($img);
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
            if($sourceImage) {
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
            }
        } else {
            user_error("Error: Neither Imagick nor GD is installed.\n");
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
        } elseif (extension_loaded('gd')) {
            self::$useGd = true;
        } else {
            exit("Error: Neither Imagick nor GD is installed.\n");
        }
    }
    protected static function isFileSizeGreaterThan(string $filePath): ?float
    {
        $fileSize = filesize($filePath);
        $maxSize = self::$max_file_size_in_mb * 1024 * 1024;
        if ($fileSize > $maxSize) {
            return round(($fileSize - $maxSize) / $maxSize * 100);
        }
        return null;
    }
}
