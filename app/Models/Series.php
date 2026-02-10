<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'poster_image',
        'image_url',
        'source_url',
        'type',
    ];

    /**
     * 🧠 LOGIKA PINTAR 1: Untuk Poster Image
     * Otomatis deteksi apakah ini Link Luar atau File Lokal.
     */
    public function getPosterImageAttribute($value)
    {
        // Kalau kosong, kembalikan gambar default
        if (!$value) {
            return asset('default.jpg');
        }

        // Kalau link diawali 'http', berarti link luar (jangan di-asset)
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // Kalau bukan http, berarti file lokal (pakai asset)
        return asset($value);
    }

    /**
     * 🧠 LOGIKA PINTAR 2: Untuk Image URL
     * Sama seperti di atas.
     */
    public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return asset('default.jpg');
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return asset($value);
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    public function latestEpisode()
    {
        return $this->hasOne(Episode::class)->latestOfMany();
    }
}