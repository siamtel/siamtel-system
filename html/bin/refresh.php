<?php

declare(strict_types=1);

/**
 * ==============================================================================
 * SIAMTEL - Reinicio Completo de Base de Datos
 * ==============================================================================
 * Ubicación: html/bin/refresh.php
 * ==============================================================================
 */

$_SERVER['argv'] = [$argv[0] ?? 'bin/migrate.php', 'fresh'];
require __DIR__ . '/migrate.php';

