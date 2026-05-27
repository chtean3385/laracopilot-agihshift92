<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_templates', 'header_format')) {
                $table->string('header_format', 20)->default('none')->after('has_document_attachment');
            }
            if (!Schema::hasColumn('whatsapp_templates', 'header_media_url')) {
                $table->text('header_media_url')->nullable()->after('header_format');
            }
            if (!Schema::hasColumn('whatsapp_templates', 'has_buttons')) {
                $table->boolean('has_buttons')->default(false)->after('header_media_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn(['header_format', 'header_media_url', 'has_buttons']);
        });
    }
};
