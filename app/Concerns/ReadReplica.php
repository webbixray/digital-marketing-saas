<?php

namespace App\Concerns;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * ReadReplica trait routes read-only queries to a configured read replica
 * database connection, while writes go to the primary connection.
 *
 * Usage:
 *   use ReadReplica;
 *
 *   public function getStats(): array
 *   {
 *       return $this->read(function () {
 *           return SocialPost::where('agency_id', $this->agency->id)->count();
 *       });
 *   }
 *
 *   public function save(): void
 *   {
 *       $this->write(function () {
 *           $this->save();
 *       });
 *   }
 */
trait ReadReplica
{
    /**
     * Execute a read-only query against the read replica connection.
     *
     * Falls back to the default connection if no read replica is configured.
     */
    protected function read(callable $callback): mixed
    {
        $originalConnection = DB::getDefaultConnection();
        $readConnection = config('database.default') === 'mysql' && config('database.connections.mysql_read')
            ? 'mysql_read'
            : config('database.default');

        try {
            DB::setDefaultConnection($readConnection);

            return $callback();
        } finally {
            DB::setDefaultConnection($originalConnection);
        }
    }

    /**
     * Execute a write query against the primary connection.
     */
    protected function write(callable $callback): mixed
    {
        return $callback();
    }

    /**
     * Get the read replica connection name.
     */
    protected function getReadConnectionName(): string
    {
        if (config('database.default') === 'mysql' && config('database.connections.mysql_read')) {
            return 'mysql_read';
        }

        return config('database.default');
    }
}
