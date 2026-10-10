<?php
// Backward-compatible aliases — shared views live in app/Views/learn/
$learnBase = $learnBase ?? '/admin/learn';
$webShell = $webShell ?? false;
require dirname(__DIR__, 2) . '/learn/index.php';
