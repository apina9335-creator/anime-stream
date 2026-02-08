<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    use HasFactory;

    /**
     * Daftar kolom yang BOLEH diisi secara otomatis (Mass Assignment).
     * Pastikan semua nama kolom ini sesuai dengan di Database.
     */
    protected $fillable = [
        'title',
        'description',
        'poster_image',
        'image_url',
        'source_url', // Link sumber (Anichin)
        'type',       // Jenis (Donghua/Anime)
    ];

    /**
     * Relasi: Satu Series memiliki BANYAK Episode.
     */
    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    /**
     * Relasi: Mengambil SATU episode paling baru.
     * Berguna untuk menampilkan "Episode Terbaru" di halaman depan.
     */
    public function latestEpisode()
    {
        return $this->hasOne(Episode::class)->latestOfMany();
    }
}