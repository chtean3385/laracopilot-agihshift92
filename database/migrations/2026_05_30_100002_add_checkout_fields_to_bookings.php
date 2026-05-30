<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('checkout_token')->nullable()->unique()->after('ota_conflict');
            $table->string('guest_payment_method')->nullable()->after('checkout_token');
            $table->string('guest_payment_ref')->nullable()->after('guest_payment_method');
            $table->timestamp('guest_checkout_submitted_at')->nullable()->after('guest_payment_ref');
        });

        // Backfill tokens for existing bookings
        DB::table('bookings')->whereNull('checkout_token')->orderBy('id')->each(function ($row) {
            DB::table('bookings')->where('id', $row->id)->update([
                'checkout_token' => (string) Str::uuid(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['checkout_token', 'guest_payment_method', 'guest_payment_ref', 'guest_checkout_submitted_at']);
        });
    }
};
