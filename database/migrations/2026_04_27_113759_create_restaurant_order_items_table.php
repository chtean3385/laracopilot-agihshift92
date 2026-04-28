<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('menu_item_id')->nullable(); // nullable in case item deleted
            $table->string('item_name');                            // snapshot of name at time of order
            $table->decimal('unit_price', 10, 2);                  // snapshot — editable at billing
            $table->decimal('final_price', 10, 2);                 // actual charged price (editable)
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 10, 2);                    // final_price * quantity
            $table->string('kot_note')->nullable();                 // e.g. "no onion", "extra spicy"
            $table->enum('food_type', ['veg', 'nonveg', 'beverage'])->default('veg');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('restaurant_orders')->onDelete('cascade');
            $table->foreign('menu_item_id')->references('id')->on('restaurant_menu_items')->onDelete('set null');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_order_items');
    }
};