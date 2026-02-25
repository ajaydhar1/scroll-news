<?php
header('Content-Type: text/plain');

echo "PHP: " . PHP_VERSION . PHP_EOL;

echo "pgsql ext: " . (extension_loaded('pgsql') ? "YES" : "NO") . PHP_EOL;
echo "pdo ext: " . (extension_loaded('pdo') ? "YES" : "NO") . PHP_EOL;
echo "pdo_pgsql ext: " . (extension_loaded('pdo_pgsql') ? "YES" : "NO") . PHP_EOL;

print_r(PDO::getAvailableDrivers());