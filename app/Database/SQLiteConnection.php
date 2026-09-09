<?php

namespace App\Database;

use Illuminate\Database\SQLiteConnection as BaseSQLiteConnection;
use PDO;

class SQLiteConnection extends BaseSQLiteConnection
{
    /**
     * Run the statement to start a new transaction.
     *
     * @return void
     */
    protected function executeBeginTransactionStatement()
    {
        if (version_compare(PHP_VERSION, '8.4.0', '>=')) {
            $mode = $this->getConfig('transaction_mode') ?? 'DEFERRED';

            // Check if we're already in a transaction to avoid "cannot start a transaction within a transaction"
            if ($this->getPdo()->inTransaction()) {
                return;
            }

            $this->getPdo()->exec("BEGIN {$mode} TRANSACTION");

            return;
        }

        $this->getPdo()->beginTransaction();
    }
}