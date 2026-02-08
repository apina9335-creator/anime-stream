<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('series', function (Blueprint $table) {
            // 1. Kita tambah kolom source_url (Tanpa syarat 'after' biar gak error)
            $table->string('source_url')->nullable();

            // 2. Karena error tadi bilang 'description' gak ada, kita buatkan sekalian!
            // (Kita pakai pengecekan biar aman)
            if (!Schema::hasColumn('series', 'description')) {
                $table->text('description')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn('source_url');
            // Kita gak drop description takutnya data lama hilang, biarkan saja
        });
    }
};