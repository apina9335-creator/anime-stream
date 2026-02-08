<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Series;
use App\Models\Episode;

class AnimeController extends Controller
{
    // 1. Halaman Depan (Home)
    public function index()
    {
        // Ambil anime terbaru yang sudah punya episode
        $series = Series::whereHas('episodes')
                        ->with('latestEpisode')
                        ->latest('updated_at')
                        ->get();
                        
        return view('home', compact('series'));
    }

    // 2. Halaman Nonton (Watch)
    public function watch($id, $ep)
    {
        // Cari Animenya
        $anime = Series::findOrFail($id);

        // Cari Episode (Paksa jadi angka integer agar akurat)
        $episode = Episode::where('series_id', $id)
                          ->where('episode_number', intval($ep)) 
                          ->first();

        // Kalau episode tidak ditemukan, tampilkan error 404
        if (!$episode) {
            abort(404, 'Episode tidak ditemukan.');
        }

        // Navigasi Next/Prev
        $prevEp = Episode::where('series_id', $id)
                         ->where('episode_number', $episode->episode_number - 1)
                         ->first();

        $nextEp = Episode::where('series_id', $id)
                         ->where('episode_number', $episode->episode_number + 1)
                         ->first();

        // --- BAGIAN PENTING: URUTKAN SECARA ANGKA (NUMERIC SORT) ---
        // Kita pakai orderByRaw supaya '10' dianggap lebih besar dari '2'
        // Kalau pakai orderBy biasa, komputer mengira '10' itu depannya '1', jadi lebih kecil dari '2'
        $allEpisodes = Episode::where('series_id', $id)
                              ->orderByRaw('CAST(episode_number AS UNSIGNED) ASC') 
                              ->get();

        return view('watch', compact('anime', 'episode', 'prevEp', 'nextEp', 'allEpisodes'));
    }
}