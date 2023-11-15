example:

```php

use Sunnysideup\ResizeAssets\ResizeAssetsRunner;

if (!Director::is_cli()) {
    exit('Only works in cli');
}

echo '---' . PHP_EOL;
echo '---' . PHP_EOL;


$directory = '/container/app/public/assets/';
$maxWidth = 3000;
$maxHeight = 2000;
$maxSize = 2;
$quality = 0.77;
$largeSizeQuality = 0.67;
$dryRun = !in_array('--real-run', $_SERVER['argv']); // Pass --dry-run as an argument to perform a dry run

echo "--- DIRECTORY: " . $directory . PHP_EOL;
echo "--- MAX-WIDTH: " . $maxWidth . PHP_EOL;
echo "--- MAX-HEIGHT: " . $maxHeight . PHP_EOL;
echo "--- MAX-SIZE: " . $maxSize . PHP_EOL;
echo "--- MAX-SIZE: " . $quality . PHP_EOL;
echo "--- MAX-SIZE: " . $largeSizeQuality . PHP_EOL;
echo "--- DRY-RUN: " . ($dryRun ? 'YES' : 'NO') . PHP_EOL;
// RUN!

ResizeAssetsRunner::set_max_file_size_in_mb($maxSize);
ResizeAssetsRunner::set_default_quality(0.77);
ResizeAssetsRunner::set_large_size_default_quality(0.67);

ResizeAssetsRunner::run_dir($directory, $maxWidth, $maxHeight, $dryRun);

echo '---' . PHP_EOL;
echo '---' . PHP_EOL;
echo 'DONE '. PHP_EOL;
echo '---' . PHP_EOL;
```
