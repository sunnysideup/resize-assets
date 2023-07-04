
example:

```php

use Sunnysideup\ResizeAssets\ResizeAssetsRunner;

echo "---".PHP_EOL;
echo "---".PHP_EOL;

$directory = "/container/application/public/assets";
$maxWidth = 1600;
$maxHeight = 1600;
$dryRun = isset($argv[1]) && $argv[1] === "--dry-run"; // Pass --dry-run as an argument to perform a dry run


ResizeAssetsRunner::set_gd_as_converter(); // OPTIONAL!
ResizeAssetsRunner::set_imagick_as_converter(); // OPTIONAL!

// RUN!
ResizeAssetsRunner::run_dir($directory, $maxWidth, $maxHeight, $dryRun);

echo "Operation completed.".PHP_EOL;

```

