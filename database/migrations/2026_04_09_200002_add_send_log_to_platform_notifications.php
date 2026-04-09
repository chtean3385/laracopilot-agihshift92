<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('platform_notifications', function (Blueprint $table) {
            $table->text('send_log')->nullable()->after('delivered_count');
            $table->integer('token_count')->default(0)->after('send_log');
        });
    }
    public function down(): void
    {
        Schema::table('platform_notifications', function (Blueprint $table) {
            $table->dropColumn(['send_log', 'token_count']);
        });
    }
};
