<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->json('authors')->nullable();
            $table->string('cover_image_url', 1000)->nullable();
            $table->text('description')->nullable();
            $table->integer('page_count')->nullable();
            $table->string('published_date')->nullable();
            $table->string('main_category')->nullable();
            $table->decimal('average_rating', 3, 2)->unsigned()->nullable();
            $table->string('google_books_id')->nullable();
            $table->enum('status', ['For Purchase', 'Owned', 'Reading', 'Read'])->default('For Purchase');
            $table->date('purchase_date')->nullable();
            $table->integer('price')->nullable();
            $table->date('start_reading_date')->nullable();
            $table->date('finish_reading_date')->nullable();
            $table->integer('reading_progress')->default(0);
            $table->text('personal_notes')->nullable();
            $table->decimal('personal_rating', 3, 2)->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
