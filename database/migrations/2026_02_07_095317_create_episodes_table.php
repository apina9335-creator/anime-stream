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
    Schema::create('episodes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('series_id')->constrained('series')->onDelete('cascade');
        $table->string('episode_number'); // Episode 1, 2, dst
       // Ganti baris video_url yang lama dengan ini:
        $table->json('video_url')->nullable();      // Link Embed Video (Text biar muat panjang)
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
