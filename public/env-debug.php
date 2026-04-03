<?php
header('Content-Type: text/plain');
echo "=== ALL ENV VARS ===\n";
foreach (getenv() as $k => $v) {
    echo "$k=" . (strlen($v) > 50 ? substr($v,0,50).'...' : $v) . "\n";
}
echo "\n=== _ENV count: " . count($_ENV) . " ===\n";
foreach ($_ENV as $k => $v) {
    echo "ENV: $k=" . (strlen($v) > 50 ? substr($v,0,50).'...' : $v) . "\n";
}
