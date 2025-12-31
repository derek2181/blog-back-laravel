<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pageId')->constrained('pages')->cascadeOnDelete();
            $table->enum('type', [
                'HERO',
                'TEXT',
                'TAGS',
                'FACTS',
                'PASSIONS',
                'GALLERY',
                'CTA',
                'IMAGE',
                'LIST',
                'GROUP',
                'SHOWCASE',
                'SOCIAL',
                'POSTS',
                'NEWSLETTER',
            ]);
            $table->integer('order')->default(0);
            $table->json('content')->nullable();
            $table->string('imagePath')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('createdAt')->useCurrent();
            $table->dateTime('updatedAt')->useCurrent()->useCurrentOnUpdate();

            $table->index('pageId');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_blocks');
    }
};
