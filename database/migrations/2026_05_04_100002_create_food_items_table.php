<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id')->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('food_categories')->onDelete('set null');
        });

        // Pivot: food item ↔ inventory item (for auto-deduction on order approval)
        Schema::create('food_item_ingredients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_item_id')->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->decimal('quantity_per_unit', 10, 3)->default(1);
            $table->timestamps();

            $table->foreign('food_item_id')->references('id')->on('food_items')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->unique(['food_item_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_item_ingredients');
        Schema::dropIfExists('food_items');
    }
};
