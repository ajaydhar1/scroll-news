<?php
http_response_code(403);
exit('Dev tool');

header('Content-Type: text/plain');

echo "URL request reached this file ✅\n\n";

echo "PHP_VERSION: " . PHP_VERSION . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "Loaded php.ini: " . (php_ini_loaded_file() ?: 'NONE') . "\n";
echo "Scanned ini: " . (php_ini_scanned_files() ?: 'NONE') . "\n";
echo "extension_dir: " . ini_get('extension_dir') . "\n";

echo "\nPDO drivers:\n";
echo implode(", ", class_exists('PDO') ? PDO::getAvailableDrivers() : []) . "\n";

echo "\npdo_pgsql loaded: " . (extension_loaded('pdo_pgsql') ? "YES" : "NO") . "\n";
echo "pgsql loaded: " . (extension_loaded('pgsql') ? "YES" : "NO") . "\n";