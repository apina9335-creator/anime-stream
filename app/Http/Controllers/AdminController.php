<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Series;
use App\Models\ScraperLog;
use Illuminate\Support\Facades\Artisan;

class AdminController extends Controller
{
    public function index()
    {
        $series = Series::withCount('episodes')->orderBy('id', 'desc')->get();
        return view('admin.dashboard', compact('series'));
    }

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
            'poster_image' => 'default.jpg',
            'image_url' => 'default.jpg',
            'type' => 'Donghua'
        ]);

        return back()->with('success', '✅ Anime baru berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $anime = Series::findOrFail($id);
        return view('admin.edit', compact('anime'));
    }

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

        if ($request->hasFile('poster_image')) {
            try {
                $file = $request->file('poster_image');
                $filename = time() . '_' . $file->getClientOriginalName();
                if (!file_exists(public_path('uploads'))) {
                    mkdir(public_path('uploads'), 0777, true);
                }
                $file->move(public_path('uploads'), $filename);
                $dataToUpdate['poster_image'] = 'uploads/' . $filename;
                $dataToUpdate['image_url'] = 'uploads/' . $filename; 
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal upload gambar: ' . $e->getMessage());
            }
        }

        $anime->update($dataToUpdate);
        return redirect()->route('admin.dashboard')->with('success', '✅ Data & Gambar berhasil diperbarui!');
    }

    // --- 5. JALANKAN ROBOT (VERSI BACKGROUND WINDOWS) ---
   public function runScraper(Request $request, $id)
    {
        $anime = Series::findOrFail($id);

        if ($anime->source_url) {
            // 1. Ambil jalur lengkap ke file artisan dan log
            $artisan = base_path('artisan');
            $logPath = storage_path('logs/scraper_debug.log');
            $phpPath = PHP_BINARY;
            $url = $anime->source_url;

            // 2. Trik Tanda Kutip: Kita bungkus semua jalur dengan kutip dua (") 
            // agar folder "New folder" tidak memutus perintah
            $command = "\"$phpPath\" \"$artisan\" anime:grab \"$url\" $id > \"$logPath\" 2>&1";

            if (PHP_OS_FAMILY === 'Windows') {
                // Gunakan /S untuk memberi tahu Windows bahwa perintah di dalamnya dibungkus kutip
                pclose(popen("start /B cmd /S /C $command", "r"));
            } else {
                exec($command . " > /dev/null 2>&1 &");
            }
            
            // 3. Tambahkan catatan ke Laravel Log biasa sebagai bukti controller ini dipicu
            \Illuminate\Support\Facades\Log::info("Tombol Update diklik untuk anime ID: $id");
            
            return back()->with('success', "🚀 Robot diluncurkan! Tunggu 5 detik lalu cek folder storage/logs.");
        }

        return back()->with('error', '⚠️ Link sumber belum diisi!');
    }

    

    // --- FITUR BARU: UPDATE SEMUA ---
    public function updateAll()
    {
        $allSeries = Series::whereNotNull('source_url')->get();
        if ($allSeries->isEmpty()) return back()->with('error', 'Tidak ada anime untuk diupdate.');

        $artisan = base_path('artisan');
        foreach ($allSeries as $anime) {
            $command = "php \"$artisan\" anime:grab \"{$anime->source_url}\" {$anime->id}";
            pclose(popen("start /B $command", "r"));
        }

        return back()->with('success', "🔥 Robot massal diluncurkan untuk " . $allSeries->count() . " anime!");
    }

    public function getLogs()
    {
        if (class_exists('App\Models\ScraperLog')) {
            $logs = ScraperLog::latest()->take(50)->get();
            return response()->json($logs);
        }
        return response()->json([]);
    }
}