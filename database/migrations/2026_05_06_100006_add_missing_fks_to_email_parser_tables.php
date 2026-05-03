<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        $this->addFkIfMissing('parsed_emails', 'booking_id', 'bookings', 'id', 'set null', $driver);
        $this->addFkIfMissing('ota_booking_conflicts', 'parsed_email_id', 'parsed_emails', 'id', 'set null', $driver);
        $this->addFkIfMissing('ota_booking_conflicts', 'resolved_by', 'users', 'id', 'set null', $driver);
    }

    public function down(): void
    {
    }

    private function addFkIfMissing(string $table, string $column, string $refTable, string $refColumn, string $onDelete, string $driver): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || !Schema::hasTable($refTable)) {
            return;
        }

        if ($this->fkExists($table, $column, $driver)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $t) use ($column, $refTable, $refColumn, $onDelete) {
                $t->foreign($column)->references($refColumn)->on($refTable)->onDelete($onDelete);
            });
        } catch (\Throwable $e) {
            // Ignore — orphan rows or driver quirks shouldn't block deploy.
        }
    }

    private function fkExists(string $table, string $column, string $driver): bool
    {
        try {
            if ($driver === 'pgsql') {
                $rows = DB::select(
                    "SELECT 1 FROM information_schema.key_column_usage kcu
                     JOIN information_schema.table_constraints tc
                       ON tc.constraint_name = kcu.constraint_name
                      AND tc.table_name = kcu.table_name
                     WHERE tc.constraint_type = 'FOREIGN KEY'
                       AND kcu.table_name = ?
                       AND kcu.column_name = ?",
                    [$table, $column]
                );
                return !empty($rows);
            }
            if ($driver === 'mysql') {
                $rows = DB::select(
                    "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
                      WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = ?
                        AND COLUMN_NAME = ?
                        AND REFERENCED_TABLE_NAME IS NOT NULL",
                    [$table, $column]
                );
                return !empty($rows);
            }
            if ($driver === 'sqlite') {
                $rows = DB::select("PRAGMA foreign_key_list({$table})");
                foreach ($rows as $r) {
                    if (($r->from ?? null) === $column) return true;
                }
                return false;
            }
        } catch (\Throwable $e) {
            return true;
        }
        return false;
    }
};
