<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('image');
            $table->string('category');
            $table->enum('type', ['ALBUM', 'HOBBY', 'BLOG']);
            $table->integer('releaseYear')->nullable();
            $table->dateTime('postedAt')->nullable();
            $table->dateTime('updatedAt')->nullable();
            $table->string('readTime')->nullable();
            $table->string('video')->nullable();
            $table->string('tagsJson')->nullable()->default('');
            $table->string('galleryJson')->nullable()->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
