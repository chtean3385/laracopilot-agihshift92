<?php
/**
 * Redirect project root to public/ folder.
 * This works even without mod_rewrite enabled.
 */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$uri  = $_SERVER['REQUEST_URI'];

// Strip the base path from URI to get just the route portion
$route = '/' . ltrim(substr($uri, strlen($base)), '/');

// Redirect to public/ preserving the route
header('Location: ' . $base . '/public' . $route, true, 301);
exit;
