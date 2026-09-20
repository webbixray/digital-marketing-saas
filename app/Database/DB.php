<?php

namespace App\Database;

use Illuminate\Database\Connection;

/**
 * Database helper for read/write split connections.
 *
 * Usage:
 *   use App\Database\DB;
 *   $results = DB::read('mysql_read')->select(...);
 *   DB::write('mysql_write')->insert(...);
 *
 * Or simply:
 *   $results = DB::read()->table('users')->get();
 *   DB::write()->table('users')->insert([...]);
 */
class DB
{
    /**
     * Get a read (replica) database connection.
     * Falls back to default 'mysql' if no read replica is configured.
     */
    public static function read(?string $connection = null): Connection
    {
        $conn = $connection ?? self::resolveReadConnection();
        return \Illuminate\Support\Facades\DB::connection($conn);
    }

    /**
     * Get a write (master) database connection.
     * Falls back to default 'mysql' if no write master is configured.
     */
    public static function write(?string $connection = null): Connection
    {
        $conn = $connection ?? self::resolveWriteConnection();
        return \Illuminate\Support\Facades\DB::connection($conn);
    }

    /**
     * Determine the appropriate read connection.
     * Uses 'mysql_read' if DB_READ_HOST differs from default.
     * Falls back to 'mysql' for single-server setups.
     */
    private static function resolveReadConnection(): string
    {
        $readHost = \Illuminate\Support\Facades\Config::get('database.connections.mysql_read.host');
        $defaultHost = \Illuminate\Support\Facades\Config::get('database.connections.mysql.host');

        // If read host is configured and different from default, use it
        if ($readHost && $readHost !== $defaultHost) {
            return 'mysql_read';
        }

        return 'mysql';
    }

    /**
     * Determine the appropriate write connection.
     * Uses 'mysql_write' if DB_WRITE_HOST differs from default.
     * Falls back to 'mysql' for single-server setups.
     */
    private static function resolveWriteConnection(): string
    {
        $writeHost = config('database.connections.mysql_write.host');
        $defaultHost = config('database.connections.mysql.host');

        // If write host is configured and different from default, use it
        if ($writeHost && $writeHost !== $defaultHost) {
            return 'mysql_write';
        }

        return 'mysql';
    }

    /**
     * Check if read/write split is configured (hosts differ).
     */
    public static function hasReadSplit(): bool
    {
        return self::resolveReadConnection() !== 'mysql';
    }

    /**
     * Check if write split is configured.
     */
    public static function hasWriteSplit(): bool
    {
        return self::resolveWriteConnection() !== 'mysql';
    }

    /**
     * Execute a callback within a transaction on the write connection.
     */
    public static function transaction(callable $callback, int $attempts = 1): mixed
    {
        return self::write()->transaction($callback, $attempts);
    }
}
