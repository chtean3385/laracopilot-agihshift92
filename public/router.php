<?php

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Serve static files (CSS, JS, images, etc.) directly from public/
if ($uri !== '/' && file_exists(__DIR__.$uri)) {
    return false;
}

// All other requests go through Laravel
require_once __DIR__.'/index.php';
