<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ota_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sender_number')->nullable();
            $table->string('waba_id')->nullable();
            $table->string('message_pattern_key')->default('generic');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('ota_sources')->insert([
            ['name' => 'Booking.com',  'sender_number' => null,          'waba_id' => null, 'message_pattern_key' => 'booking_com',  'notes' => 'Booking.com WhatsApp Business sender',     'is_active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Airbnb',       'sender_number' => null,          'waba_id' => null, 'message_pattern_key' => 'airbnb',       'notes' => 'Airbnb WhatsApp Business sender',          'is_active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Agoda',        'sender_number' => null,          'waba_id' => null, 'message_pattern_key' => 'agoda',        'notes' => 'Agoda WhatsApp sender',                   'is_active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'MakeMyTrip',   'sender_number' => null,          'waba_id' => null, 'message_pattern_key' => 'makemytrip',   'notes' => 'MakeMyTrip WhatsApp sender',               'is_active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Goibibo',      'sender_number' => null,          'waba_id' => null, 'message_pattern_key' => 'goibibo',      'notes' => 'Goibibo WhatsApp sender',                 'is_active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Expedia',      'sender_number' => null,          'waba_id' => null, 'message_pattern_key' => 'expedia',      'notes' => 'Expedia / Hotels.com sender',              'is_active' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Test/Generic', 'sender_number' => '917043069225','waba_id' => null, 'message_pattern_key' => 'generic',      'notes' => 'SaaS admin test number for demo testing',  'is_active' => true,  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ota_sources');
    }
};
