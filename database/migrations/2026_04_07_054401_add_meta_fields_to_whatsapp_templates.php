<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->string('meta_template_id')->nullable()->after('approval_status')
                  ->comment('ID returned by Meta after template submission');
            $table->string('meta_status')->default('not_submitted')->after('meta_template_id')
                  ->comment('not_submitted|submitted|approved|rejected');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn(['meta_template_id', 'meta_status']);
        });
    }
};
