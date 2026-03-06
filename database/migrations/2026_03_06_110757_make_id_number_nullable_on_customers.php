<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // id_number is now optional - documents are stored in customer_documents table.
        // SQLite does not support column modification; application layer defaults id_number to ''.
    }

    public function down(): void
    {
        //
    }
};
