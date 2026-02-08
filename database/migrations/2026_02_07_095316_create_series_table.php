<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('series', function (Blueprint $table) {
        $table->id();
        $table->string('title');       // Judul Anime
        $table->string('type');        // TV / ONA / MOVIE
        $table->string('image_url');   // Link Gambar Poster
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
