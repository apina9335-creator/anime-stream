<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = ['series_id', 'episode_number', 'video_url'];

    // --- BAGIAN INI SANGAT PENTING ---
    // Ini mengubah teks '["link"]' menjadi array PHP ['link'] otomatis
    protected $casts = [
        'video_url' => 'array', 
    ];

    public function series()
    {
        return $this->belongsTo(Series::class);
    }
}