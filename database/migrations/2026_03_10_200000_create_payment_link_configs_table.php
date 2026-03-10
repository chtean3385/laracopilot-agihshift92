<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_link_configs', function (Blueprint $table) {
            $table->id();
            $table->string('upi_id')->nullable();
            $table->string('upi_name')->nullable();
            $table->boolean('upi_enabled')->default(false);
            $table->string('razorpay_key_id')->nullable();
            $table->string('razorpay_key_secret')->nullable();
            $table->boolean('razorpay_enabled')->default(false);
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('razorpay_payment_link_id')->nullable()->after('status');
            $table->string('razorpay_payment_link_url')->nullable()->after('razorpay_payment_link_id');
            $table->string('razorpay_payment_link_status')->nullable()->after('razorpay_payment_link_url');
        });
    }

    public function down(): void {
        Schema::dropIfExists('payment_link_configs');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['razorpay_payment_link_id', 'razorpay_payment_link_url', 'razorpay_payment_link_status']);
        });
    }
};
