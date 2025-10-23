#!/usr/bin/env php
<?php

/**
 * Setup script for test database
 * Creates a separate test database and runs migrations
 */

declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'host.docker.internal';
$port = getenv('DB_PORT') ?: '5432';
$user = getenv('DB_USER') ?: 'rhd';
$password = getenv('DB_PASSWORD') ?: 'rhd';
$testDbName = 'rhd_test';
$pdo = new PDO(
    "pgsql:host={$host};port={$port};dbname=postgres",
    $user,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("DROP DATABASE IF EXISTS {$testDbName}");
$pdo->exec("CREATE DATABASE {$testDbName}");
unset($pdo);

$pdo = new PDO(
    "pgsql:host={$host};port={$port};dbname={$testDbName}",
    $user,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sqlFiles = [
    'src/Infra/enable_postgis.sql',
    'src/User/Infra/users.sql',
    'src/Driver/Infra/drivers.sql',
    'src/Ride/Infra/rides.sql',
];

foreach ($sqlFiles as $sqlFile) {
    $sql = file_get_contents($sqlFile);

    if ($sql === false) {
        echo "    Error: Could not read file.\n";
        exit(1);
    }

    $pdo->exec($sql);
}
