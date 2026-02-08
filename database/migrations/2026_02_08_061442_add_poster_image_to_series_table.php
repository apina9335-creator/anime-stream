<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('series', function (Blueprint $table) {
            // Kita cek dulu biar gak error kalau ternyata udah ada
            if (!Schema::hasColumn('series', 'poster_image')) {
                $table->string('poster_image')->nullable()->default('default.jpg');
            }
        });
    }

    public function down()
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn('poster_image');
        });
    }
};