<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('itemId')->constrained('items')->cascadeOnDelete();
            $table->uuid('imageAssetId');
            $table->enum('role', ['PRIMARY', 'GALLERY']);
            $table->integer('order')->default(0);
            $table->dateTime('createdAt')->useCurrent();

            $table->index('itemId');
            $table->index('imageAssetId');
            $table->unique(['itemId', 'role', 'imageAssetId']);
            $table->unique(['itemId', 'role', 'order']);

            $table->foreign('imageAssetId')->references('id')->on('image_assets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_images');
    }
};
