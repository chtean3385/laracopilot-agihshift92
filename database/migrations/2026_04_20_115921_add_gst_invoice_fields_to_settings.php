<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'invoice_style')) {
                $table->string('invoice_style')->default('modern')->after('food_tax_rate');
            }
            if (!Schema::hasColumn('settings', 'contact_number')) {
                $table->string('contact_number')->nullable()->after('invoice_style');
            }
            if (!Schema::hasColumn('settings', 'state_code')) {
                $table->string('state_code')->nullable()->after('contact_number');
            }
            if (!Schema::hasColumn('settings', 'hsn_room')) {
                $table->string('hsn_room')->default('996311')->after('state_code');
            }
            if (!Schema::hasColumn('settings', 'hsn_food')) {
                $table->string('hsn_food')->default('996331')->after('hsn_room');
            }
            if (!Schema::hasColumn('settings', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('hsn_food');
            }
            if (!Schema::hasColumn('settings', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('settings', 'bank_ifsc')) {
                $table->string('bank_ifsc')->nullable()->after('bank_account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_style', 'contact_number', 'state_code',
                'hsn_room', 'hsn_food', 'bank_name', 'bank_account_number', 'bank_ifsc',
            ]);
        });
    }
};
