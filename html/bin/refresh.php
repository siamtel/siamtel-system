<?php

declare(strict_types=1);

$module = $argv[1] ?? 'all';
$_SERVER['argv'] = [$argv[0] ?? 'bin/migrate.php', 'fresh', $module];
require __DIR__ . '/migrate.php';
