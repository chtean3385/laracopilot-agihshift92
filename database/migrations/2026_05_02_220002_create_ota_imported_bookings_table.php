<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ota_imported_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->unsignedBigInteger('ota_source_id')->nullable();
            $table->text('raw_message');
            $table->string('booking_ref')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('guest_phone')->nullable();
            $table->date('checkin')->nullable();
            $table->date('checkout')->nullable();
            $table->string('room_type')->nullable();
            $table->string('guests_count')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->text('special_request')->nullable();
            $table->string('ota_name')->nullable();
            $table->string('property_name')->nullable();
            $table->string('matched_by')->default('number_only');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->index(['hotel_id', 'status']);
            $table->index('booking_ref');
        });

        if (Schema::hasTable('bookings')) {
            if (!Schema::hasColumn('bookings', 'ota_ref')) {
                Schema::table('bookings', function (Blueprint $table) {
                    $table->string('ota_ref')->nullable()->after('source');
                    $table->string('ota_name')->nullable()->after('ota_ref');
                });
            }
        }

        if (Schema::hasTable('hotels')) {
            if (!Schema::hasColumn('hotels', 'ota_alias')) {
                Schema::table('hotels', function (Blueprint $table) {
                    $table->string('ota_alias')->nullable()->after('name');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ota_imported_bookings');

        if (Schema::hasTable('bookings')) {
            if (Schema::hasColumn('bookings', 'ota_ref')) {
                Schema::table('bookings', fn($t) => $t->dropColumn(['ota_ref', 'ota_name']));
            }
        }

        if (Schema::hasTable('hotels')) {
            if (Schema::hasColumn('hotels', 'ota_alias')) {
                Schema::table('hotels', fn($t) => $t->dropColumn('ota_alias'));
            }
        }
    }
};
