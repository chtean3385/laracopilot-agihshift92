<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add source + ota_conflict to bookings (safe, nullable) ──────────
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'source')) {
                $table->string('source')->nullable()->after('special_requests');
            }
            if (!Schema::hasColumn('bookings', 'ota_conflict')) {
                $table->boolean('ota_conflict')->default(false)->after('source');
            }
        });

        // ── 2. booking_widget_settings ─────────────────────────────────────────
        if (!Schema::hasTable('booking_widget_settings')) {
            Schema::create('booking_widget_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hotel_id')->unique();
                $table->string('widget_title')->default('Book Your Stay');
                $table->string('primary_color', 10)->default('#6366f1');
                $table->string('button_text')->default('Book Now');
                $table->unsignedSmallInteger('min_advance_hours')->default(2);
                $table->unsignedSmallInteger('max_advance_days')->default(365);
                $table->boolean('auto_confirm')->default(false);
                $table->boolean('require_advance_payment')->default(false);
                $table->decimal('advance_payment_amount', 10, 2)->default(0);
                $table->string('upi_id')->nullable();
                $table->string('upi_qr_image')->nullable();
                $table->string('default_country_code', 5)->default('IN');
                $table->boolean('show_room_photos')->default(true);
                $table->boolean('show_prices')->default(true);
                $table->text('thank_you_message')->nullable();
                $table->timestamps();
            });
        }

        // ── 3. booking_payment_references ─────────────────────────────────────
        if (!Schema::hasTable('booking_payment_references')) {
            Schema::create('booking_payment_references', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('booking_id');
                $table->enum('payment_type', ['upi', 'cash', 'other'])->default('upi');
                $table->string('reference_number');
                $table->decimal('amount', 10, 2)->default(0);
                $table->enum('submitted_by', ['guest', 'admin'])->default('guest');
                $table->text('notes')->nullable();
                $table->boolean('verified')->default(false);
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        // ── 4. ota_booking_conflicts (shared with Task #58, create if missing) ─
        if (!Schema::hasTable('ota_booking_conflicts')) {
            Schema::create('ota_booking_conflicts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hotel_id');
                $table->unsignedBigInteger('booking_id');
                $table->unsignedBigInteger('parsed_email_id')->nullable();
                $table->enum('conflict_type', ['room_type_unavailable', 'dates_overlap', 'no_room_matched'])->default('dates_overlap');
                $table->string('requested_room_type')->nullable();
                $table->date('check_in_date')->nullable();
                $table->date('check_out_date')->nullable();
                $table->boolean('resolved')->default(false);
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        // ── 5. Seed booking-widget module for all existing hotels ──────────────
        $hotelIds = DB::table('hotels')->pluck('id');
        foreach ($hotelIds as $hotelId) {
            $exists = DB::table('modules')
                ->where('hotel_id', $hotelId)
                ->where('slug', 'booking-widget')
                ->exists();
            if (!$exists) {
                DB::table('modules')->insert([
                    'hotel_id'    => $hotelId,
                    'slug'        => 'booking-widget',
                    'name'        => 'Booking Widget',
                    'description' => 'Embeddable website booking form. Guests book directly from your hotel website and bookings appear in CRM instantly.',
                    'is_enabled'  => false,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        // ── 6. Seed website_booking_received platform WA template ─────────────
        $exists = DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->where('trigger_event', 'website.booking.received')
            ->exists();
        if (!$exists) {
            DB::table('whatsapp_templates')->insert([
                'hotel_id'        => null,
                'trigger_event'   => 'website.booking.received',
                'template_name'   => 'website_booking_received',
                'message_body'    => "New website booking for {{1}}!\n\nGuest: {{2}} (Ph: {{3}})\nRoom Type: {{4}}\nCheck-in: {{5}}\nCheck-out: {{6}}\nBooking: {{7}}\n\nLog in to confirm and assign a room.",
                'variables_hint'  => '{{hotel_name}}, {{guest_name}}, {{phone}}, {{room_type}}, {{check_in}}, {{check_out}}, {{booking_number}}',
                'is_active'       => true,
                'approval_status' => 'pending',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('modules')->where('slug', 'booking-widget')->delete();
        DB::table('whatsapp_templates')->whereNull('hotel_id')->where('trigger_event', 'website.booking.received')->delete();
        Schema::dropIfExists('ota_booking_conflicts');
        Schema::dropIfExists('booking_payment_references');
        Schema::dropIfExists('booking_widget_settings');
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'ota_conflict')) $table->dropColumn('ota_conflict');
            if (Schema::hasColumn('bookings', 'source'))       $table->dropColumn('source');
        });
    }
};
