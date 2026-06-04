<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Store file bytes as base64 in Neon — survives every deployment
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->longText('file_content')->nullable()->after('file_size');
        });

        Schema::table('booking_guests', function (Blueprint $table) {
            $table->longText('id_document_content')->nullable()->after('id_document_name');
            $table->string('id_document_mime')->nullable()->after('id_document_content');
        });

        Schema::table('guest_checkin_requests', function (Blueprint $table) {
            $table->longText('id_document_content')->nullable()->after('id_document_path');
            $table->string('id_document_mime')->nullable()->after('id_document_content');
        });

        // customers table — id document stored on guest profile
        if (!Schema::hasColumn('customers', 'id_document_content')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->longText('id_document_content')->nullable();
                $table->string('id_document_mime')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropColumn('file_content');
        });
        Schema::table('booking_guests', function (Blueprint $table) {
            $table->dropColumn(['id_document_content', 'id_document_mime']);
        });
        Schema::table('guest_checkin_requests', function (Blueprint $table) {
            $table->dropColumn(['id_document_content', 'id_document_mime']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['id_document_content', 'id_document_mime']);
        });
    }
};
