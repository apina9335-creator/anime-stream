<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Series;
use App\Models\ScraperLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    // --- 1. HALAMAN DASHBOARD ---
    public function index()
    {
        $series = Series::withCount('episodes')->orderBy('updated_at', 'desc')->get();
        return view('admin.dashboard', compact('series'));
    }

    // --- 2. TAMBAH ANIME BARU ---
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'source_url' => 'required|url'
        ]);

        Series::create([
            'title' => $request->title,
            'source_url' => $request->source_url,
            'description' => 'Anime seru', 
            'poster_image' => 'default.jpg', // Nanti bisa diedit
            'type' => 'Donghua'
        ]);

        return back()->with('success', '✅ Anime baru berhasil ditambahkan!');
    }

    // --- 3. HALAMAN EDIT ---
    public function edit($id)
    {
        $anime = Series::findOrFail($id);
        return view('admin.edit', compact('anime'));
    }

    // --- 4. UPDATE DATA ANIME (GAMBAR DLL) ---
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'source_url' => 'required|url',
            'poster_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', 
        ]);

        $anime = Series::findOrFail($id);
        $dataToUpdate = [
            'title' => $request->title,
            'source_url' => $request->source_url,
        ];

        // Logika Upload Gambar
        if ($request->hasFile('poster_image')) {
            try {
                $file = $request->file('poster_image');
                $filename = time() . '_' . preg_replace('/\s+/', '', $file->getClientOriginalName());
                
                // Pastikan folder ada
                if (!file_exists(public_path('uploads'))) {
                    mkdir(public_path('uploads'), 0777, true);
                }
                
                $file->move(public_path('uploads'), $filename);
                $dataToUpdate['poster_image'] = 'uploads/' . $filename;
                
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal upload gambar: ' . $e->getMessage());
            }
        }

        $anime->update($dataToUpdate);
        return redirect()->route('admin.dashboard')->with('success', '✅ Data berhasil diperbarui!');
    }

    // --- 5. JALANKAN ROBOT (SATU ANIME) ---
    // Versi Railway (Pakai Artisan::call biar stabil)
    public function runScraper($id)
    {
        $anime = Series::findOrFail($id);

        if (!$anime->source_url) {
            return back()->with('error', '⚠️ Link sumber (source_url) belum diisi! Edit dulu.');
        }

        try {
            // Jalankan perintah robot secara langsung
            // Ini akan loading sebentar (Synchronous) sampai robot selesai
            Artisan::call('anime:grab', [
                'url' => $anime->source_url,
                'series_id' => $anime->id
            ]);
            
            return back()->with('success', "✅ Sukses! Robot selesai mengecek {$anime->title}.");
            
        } catch (\Exception $e) {
            return back()->with('error', "❌ Robot Error: " . $e->getMessage());
        }
    }

    // --- 6. UPDATE SEMUA ANIME (MASSAL) ---
    public function updateAll()
    {
        // Ambil anime yang punya link sumber saja
        $allSeries = Series::whereNotNull('source_url')->get();
        
        if ($allSeries->isEmpty()) {
            return back()->with('error', '⚠️ Tidak ada anime yang memiliki link sumber.');
        }

        $count = 0;
        foreach ($allSeries as $anime) {
            try {
                // Panggil robot satu per satu
                Artisan::call('anime:grab', [
                    'url' => $anime->source_url,
                    'series_id' => $anime->id
                ]);
                $count++;
            } catch (\Exception $e) {
                // Kalau satu error, lanjut ke yang lain
                Log::error("Gagal update {$anime->title}: " . $e->getMessage());
                continue;
            }
        }

        return back()->with('success', "🔥 Selesai! Berhasil memerintahkan robot untuk $count anime.");
    }

    // --- 7. AMBIL LOG LIVE (UNTUK AJAX) ---
    public function getLogs()
    {
        if (class_exists('App\Models\ScraperLog')) {
            $logs = ScraperLog::latest()->take(50)->get();
            return response()->json($logs);
        }
        return response()->json([]);
    }
}