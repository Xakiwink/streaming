<?php
/**
 * Upload limits diagnostic (same PHP as upload.php)
 * Open in browser: /streaming/api/videos/upload_check.php
 * Delete this file after fixing uploads.
 */
header('Content-Type: text/plain; charset=utf-8');

$ini = php_ini_loaded_file();
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$server = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';

echo "=== PHP limits (as seen by this request) ===\n";
echo "upload_max_filesize = " . $upload_max . "\n";
echo "post_max_size       = " . $post_max . "\n";
echo "Loaded php.ini      = " . ($ini ?: '(none)') . "\n";
echo "Server              = " . $server . "\n\n";

if ($ini) {
    echo "=== Edit THIS file ===\n";
    echo "  sudo nano " . $ini . "\n\n";
    echo "Set these two lines (search with Ctrl+W):\n";
    echo "  upload_max_filesize = 512M\n";
    echo "  post_max_size = 520M\n\n";
}

echo "=== Then restart the right service ===\n";
if (stripos($ini, 'apache2') !== false) {
    echo "  You use Apache mod_php. Restart Apache:\n";
    echo "  sudo systemctl restart apache2\n";
} else {
    echo "  You use PHP-FPM. Restart FPM:\n";
    echo "  sudo systemctl restart php7.4-fpm\n";
}
echo "\nThen try the upload again.\n";
