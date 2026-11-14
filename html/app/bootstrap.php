<?php

declare(strict_types=1);



spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);
    $className = array_pop($parts);
    $subDir = strtolower(implode('/', $parts));
    $baseApp = __DIR__;

    $variants = [
        $className,
        strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className)),
        strtolower($className),
    ];

    $searchDirs = array_filter([$subDir]);
    if (str_starts_with($subDir, 'models/')) {
        $searchDirs[] = 'models';
    }
    if (empty($searchDirs)) {
        $searchDirs = [''];
    }

    foreach ($searchDirs as $dir) {
        $dirPrefix = $dir ? "/{$dir}" : '';
        foreach ($variants as $name) {
            $path = $baseApp . $dirPrefix . "/{$name}.php";
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    }
}, true, true);
