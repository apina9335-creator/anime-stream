<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Series;
use App\Models\Episode;

class AnimeController extends Controller
{
    // --- 1. HALAMAN DEPAN (HOME) ---
    public function index()
    {
        // Ambil anime terbaru yang sudah punya episode
        $series = Series::whereHas('episodes')
                        ->with('latestEpisode')
                        ->latest('updated_at')
                        ->get();
                        
        return view('home', compact('series'));
    }

    // --- 2. HALAMAN NONTON (WATCH) ---
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
        $allEpisodes = Episode::where('series_id', $id)
                              ->orderByRaw('CAST(episode_number AS UNSIGNED) ASC') 
                              ->get();

        return view('watch', compact('anime', 'episode', 'prevEp', 'nextEp', 'allEpisodes'));
    }

    // --- 3. FITUR PENCARIAN (SEARCH) ---
    public function search(Request $request)
    {
        $keyword = $request->input('s');

        $series = Series::whereHas('episodes') // Hanya cari yang ada episodenya
                        ->with('latestEpisode')
                        ->where('title', 'LIKE', "%{$keyword}%")
                        ->latest('updated_at')
                        ->get();

        return view('home', compact('series'));
    }

    // --- 4. FILTER: DONGHUA ---
    public function donghua()
    {
        $series = Series::whereHas('episodes')
                        ->with('latestEpisode')
                        ->where('type', 'Donghua')
                        ->latest('updated_at')
                        ->get();

        return view('home', compact('series'));
    }

    // --- 5. FILTER: ONGOING ---
    public function ongoing()
    {
        // Menampilkan semua anime (Asumsi default: yang baru update biasanya ongoing)
        // Nanti bisa disesuaikan kalau ada kolom 'status' di database
        $series = Series::whereHas('episodes')
                        ->with('latestEpisode')
                        ->latest('updated_at')
                        ->get();

        return view('home', compact('series'));
    }

    // --- 6. FILTER: COMPLETED ---
    public function completed()
    {
        // Logika sederhana: Cari yang tipenya 'Movie' atau judulnya ada kata 'Completed'
        $series = Series::whereHas('episodes')
                        ->with('latestEpisode')
                        ->where(function($query) {
                            $query->where('type', 'Movie')
                                  ->orWhere('title', 'LIKE', '%Completed%');
                        })
                        ->latest('updated_at')
                        ->get();

        return view('home', compact('series'));
    }
}