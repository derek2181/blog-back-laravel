<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('path')->unique();
            $table->enum('folderKey', ['albums', 'itzy']);
            $table->string('originalName')->nullable();
            $table->string('mimeType')->nullable();
            $table->integer('sizeBytes')->nullable();
            $table->dateTime('createdAt')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_assets');
    }
};
