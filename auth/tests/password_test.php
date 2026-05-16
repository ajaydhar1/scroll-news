<?php

$password = 'TestPassword123!';

$hash = password_hash($password, PASSWORD_DEFAULT);

echo '<pre>';

echo "Original Password:\n";
echo $password . "\n\n";

echo "Generated Hash:\n";
echo $hash . "\n\n";

$isValid = password_verify($password, $hash);

echo "Password Verify Result:\n";
echo ($isValid ? 'VALID' : 'INVALID');

echo '</pre>';