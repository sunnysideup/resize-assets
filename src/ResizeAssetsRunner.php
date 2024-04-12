<?php

namespace Sunnysideup\ResizeAssets;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Imagick;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Assets\Storage\FileHashingService;
use SilverStripe\Assets\Storage\Sha1FileHashingService;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DB;

class ResizeAssetsRunner
{
    protected static $useImagick = false;
    protected static $useGd = false;
    protected static $patterns_to_skip = [];
    protected static $max_file_size_in_mb = 2;
    protected static $default_quality = 0.77;
    protected static $large_size_default_quality = 0.67;

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
        self::$max_file_size_in_mb = $max_file_size_in_mb;
    }

    public static function set_default_quality(?float $default_quality = 0.77)
    {
        self::$default_quality =  $default_quality;
    }

    public static function set_large_size_default_quality(?float $large_size_default_quality = 0.67)
    {
        self::$large_size_default_quality = $large_size_default_quality;
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
            if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                foreach(self::$patterns_to_skip as $pattern) {
                    if(strpos($file, $pattern) !== false) {
                        continue 2;
                    }
                }
                self::run_one(
                    $file->getPathname(),
                    $maxWidth,
                    $maxHeight,
                    self::$default_quality,
                    $dryRun
                );
                $sizeCheck = self::isFileSizeGreaterThan($file->getPathname());
                if($sizeCheck) {
                    list($width, $height) = getimagesize($file->getPathname());
                    $ratio = $width / $height;
                    self::run_one(
                        $file->getPathname(),
                        round($width - (1  * $ratio)),
                        round($height - (1 * $ratio)),
                        self::$large_size_default_quality, // image quality is low to resize file size
                        $dryRun,
                        true // force resize!
                    );
                    echo 'ERROR! ' . $file . ' is still ' . $sizeCheck . '% too big!' . PHP_EOL;
                }
                $image = Image::get()->filter(['Filename' => $fullPath])->first();
                if($image && $image->exists()) {
                    self::rehash_image($image);
                }
            }
        }
    }

    public static function run_one(string $path, int $maxWidth, int $maxHeight, ?float $quality = 0.77, ?bool $dryRun = true, ?bool $force = false)
    {
        if($quality > 1 && $quality < 0.3) {
            user_error("Error: Quality should be between 0.3 and 1.0.\n");
        }
        list($width, $height) = getimagesize($path);
        if ($width <= $maxWidth && $height <= $maxHeight && !$force) {
            if($dryRun) {
                echo "-- skipping $path ({$width}x{$height})" . PHP_EOL;
            }
            return;
        }
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        if ($dryRun) {
            echo "Dry run: Would resize $path ({$width}x{$height}) to ($newWidth x $newHeight)" . PHP_EOL;
            return;
        }

        echo "Resizing $path" . PHP_EOL;


        if(self::$useImagick) {
            user_error('You will have to manually run imagick.');

            // KEEP!
            // KEEP!
            // KEEP!
            // KEEP!
            // KEEP!
            // /** @var \Imagick $Image */
            // $image = new \Imagick($img);
            // $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            // $outputFormat = self::get_output_path($path);
            // try {

            //     // Set the compression quality for JPEG
            //     if ($outputFormat === 'jpeg' || $outputFormat === 'jpg') {
            //         $jpgQuality = round($quality * 99);
            //         $image->setImageCompression(Imagick::COMPRESSION_JPEG);
            //         $image->setImageCompressionQuality($jpgQuality);
            //     }

            //     // Set the format to PNG for output if needed
            //     if ($outputFormat === 'png') {
            //         $pngQuality = round($quality * 9);
            //         $image->setImageFormat('png');
            //         $image->setImageCompressionQuality($pngQuality);
            //     }

            //     // For GIF, you may want to handle animations
            //     if ($outputFormat === 'gif') {
            //         $image = $image->coalesceImages();
            //         foreach ($image as $frame) {
            //             $frame->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            //             $frame->setImageCompressionQuality($quality);
            //         }
            //         $imagick = $image->deconstructImages();
            //         $imagick->setImageFormat('gif');
            //     }

            //     // Write the image back to the file
            //     $image->writeImages($path, true);

            //     // Clear memory
            //     $imagick->clear();
            //     $imagick->destroy();

            //     return true;
            // } catch (ImagickException $e) {
            //     error_log("An error occurred while compressing the image: " . $e->getMessage());
            //     return false;
            // }

            // KEEP!
            // KEEP!
            // KEEP!
            // KEEP!
            // KEEP!


            echo "$path NOT DONE.";
        } elseif(self::$useGd) {

            // Create a new true color image
            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // Load the image
            switch (self::get_output_path($path)) {
                case 'jpg':
                case 'jpeg':
                    $sourceImage = imagecreatefromjpeg($path);
                    break;
                case 'png':
                    $sourceImage = imagecreatefrompng($path);
                    break;
                case 'gif':
                    $sourceImage = imagecreatefromgif($path);
                    break;
            }
            if($sourceImage) {
                imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                // Save the image
                switch (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                    case 'jpg':
                    case 'jpeg':
                        $jpgQuality = round($quality * 99);
                        imagejpeg($newImage, $path, $jpgQuality);
                        break;
                    case 'png':
                        $pngQuality = round($quality * 9);
                        imagepng($newImage, $path, $pngQuality);
                        break;
                    case 'gif':
                        imagegif($newImage, $path);
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

    public static function rehash_image($image)
    {


        // RUN!
        /** @var Sha1FileHashingService $hasher */
        $hasher = Injector::inst()->get(FileHashingService::class);
        try {
            echo 'REHASHING '.$image->getFilename().PHP_EOL;
            $hasher::flush();
            if($image->isPublished()) {
                $fs = AssetStore::VISIBILITY_PUBLIC;
            } else {
                $fs = AssetStore::VISIBILITY_PROTECTED;
            }
            $hash = $hasher->computeFromFile($image->getFilename(), $fs);
            DB::query('UPDATE "File" SET "Filehash" = \''.$hash.'\' WHERE "ID" = '.$image->ID);
            if($image->isPublished()) {
                DB::query('UPDATE "File_Live" SET "Filehash" = \''.$hash.'\' WHERE "ID" = '.$image->ID);
            }
            echo 'Publishing '.$image->getFilename().PHP_EOL;
            if(! $image->exists()) {
                echo 'ERROR: Image does not exist: '.$image->getFilename().PHP_EOL;
            } else {
                $image->publishSingle();
            }
        } catch (Exception $e) {
            echo $e->getMessage().PHP_EOL;
        }

    }

    protected static function getImageResizerLib()
    {
        if(self::$useImagick || self::$useGd) {
            return;
        }
        // preferred...
        if (extension_loaded('gd')) {
            self::$useGd = true;
        } elseif (extension_loaded('imagick')) {
            self::$useImagick = true;
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


    protected static function get_output_path(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
}
