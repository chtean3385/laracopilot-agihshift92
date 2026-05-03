<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'external_booking_id')) {
                $table->string('external_booking_id')->nullable()->after('ota_name');
            }
            if (!Schema::hasColumn('bookings', 'source')) {
                $table->string('source')->nullable()->after('special_requests');
            }
            if (!Schema::hasColumn('bookings', 'ota_conflict')) {
                $table->boolean('ota_conflict')->default(false)->after('source');
            }
        });

        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'mysql', 'mariadb', 'sqlsrv'], true)) {
            $fk = $this->findRoomIdForeignKey($driver);

            if ($fk && $driver === 'pgsql') {
                DB::statement('ALTER TABLE bookings DROP CONSTRAINT ' . $fk);
            } elseif ($fk && in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE bookings DROP FOREIGN KEY ' . $fk);
            } elseif ($fk && $driver === 'sqlsrv') {
                DB::statement('ALTER TABLE bookings DROP CONSTRAINT ' . $fk);
            }

            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE bookings ALTER COLUMN room_id DROP NOT NULL');
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE bookings MODIFY room_id BIGINT UNSIGNED NULL');
            } elseif ($driver === 'sqlsrv') {
                DB::statement('ALTER TABLE bookings ALTER COLUMN room_id BIGINT NULL');
            }

            if ($fk) {
                DB::statement(
                    'ALTER TABLE bookings ADD CONSTRAINT bookings_room_id_foreign '
                    . 'FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL'
                );
            }
        } else {
            // SQLite (and other DBs): use Laravel's built-in change(), no DBAL needed in 12.x.
            Schema::table('bookings', function (Blueprint $table) {
                $table->unsignedBigInteger('room_id')->nullable()->change();
            });
        }

        Schema::table('bookings', function (Blueprint $table) {
            $idx = collect(Schema::getIndexes('bookings'))->pluck('name')->all();
            if (!in_array('bookings_hotel_external_idx', $idx, true)) {
                $table->index(['hotel_id', 'external_booking_id'], 'bookings_hotel_external_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $idx = collect(Schema::getIndexes('bookings'))->pluck('name')->all();
            if (in_array('bookings_hotel_external_idx', $idx, true)) {
                $table->dropIndex('bookings_hotel_external_idx');
            }
            if (Schema::hasColumn('bookings', 'external_booking_id')) {
                $table->dropColumn('external_booking_id');
            }
        });
    }

    private function findRoomIdForeignKey(string $driver): ?string
    {
        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                "SELECT conname FROM pg_constraint
                 WHERE conrelid = 'bookings'::regclass
                   AND contype = 'f'
                   AND conkey @> ARRAY[(
                       SELECT attnum FROM pg_attribute
                        WHERE attrelid = 'bookings'::regclass AND attname = 'room_id'
                   )]::smallint[]
                 LIMIT 1"
            );
            return $row->conname ?? null;
        }
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $row = DB::selectOne(
                "SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'bookings'
                    AND COLUMN_NAME = 'room_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                  LIMIT 1"
            );
            return $row->name ?? null;
        }
        if ($driver === 'sqlsrv') {
            $row = DB::selectOne(
                "SELECT name FROM sys.foreign_keys
                  WHERE parent_object_id = OBJECT_ID('bookings')
                  LIMIT 1"
            );
            return $row->name ?? null;
        }
        return null;
    }
};
