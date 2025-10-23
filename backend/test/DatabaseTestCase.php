<?php

declare(strict_types=1);

namespace App\Test;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that require database access.
 * Provides a PDO connection configured from phpunit.xml environment variables.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected static ?PDO $pdo = null;

    /**
     * Get a PDO connection to the test database.
     * The connection is shared across all tests for performance.
     */
    protected static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'pgsql:host=host.docker.internal;port=5432;dbname=rhd_test';
            $user = 'rhd';
            $password = 'rhd';

            self::$pdo = new PDO(
                $dsn,
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ],
            );
        }

        return self::$pdo;
    }

    /**
     * @param string[] $tables
     */
    protected function cleanupTables(array $tables): void
    {
        $pdo = self::getPdo();

        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE");
        }
    }
}
