<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnimeController;
use App\Http\Controllers\AdminController;
use App\Models\Series; 
use App\Models\User; // Tambahan untuk Magic Link
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;  // Tambahan
use Illuminate\Support\Facades\Http; // Tambahan
use Illuminate\Support\Facades\Auth; // Tambahan
use Illuminate\Http\Request;         // Tambahan

/*
|--------------------------------------------------------------------------
| JALUR ADMIN (RUANG KENDALI) 👮‍♂️
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // 1. Dashboard & Simpan Baru
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/series', [AdminController::class, 'store'])->name('admin.series.store');
    
    // 2. Jalankan Robot (Per Judul)
    Route::post('/scrape/{id}', [AdminController::class, 'runScraper'])->name('admin.scrape');

    // 🌟 FITUR BARU: UPDATE SEMUA ANIME SEKALIGUS 🌟
    Route::post('/scrape-all', [AdminController::class, 'updateAll'])->name('admin.scrape.all');

    // 3. FITUR EDIT
    Route::get('/series/{id}/edit', [AdminController::class, 'edit'])->name('admin.series.edit');
    Route::put('/series/{id}', [AdminController::class, 'update'])->name('admin.series.update');
    
    // 🔥 INI DIA YANG HILANG! JALUR HAPUS 🔥
    Route::delete('/series/{id}', [AdminController::class, 'destroy'])->name('admin.series.destroy');

    // 4. LIVE LOG
    Route::get('/logs', [AdminController::class, 'getLogs'])->name('admin.logs');
});

/*
|--------------------------------------------------------------------------
| JALUR PUBLIK (PENONTON) 🍿
|--------------------------------------------------------------------------
*/
Route::get('/', [AnimeController::class, 'index'])->name('home');
Route::get('/watch/{id}/{ep}', [AnimeController::class, 'watch'])->name('anime.watch');

/*
|--------------------------------------------------------------------------
| JALUR USER (DASHBOARD BAWAAN)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    // Ambil data anime dari database, urutkan dari yang terbaru, 20 per halaman
    $series = Series::orderBy('updated_at', 'desc')->paginate(20);
    
    // Kirim data ($series) ke tampilan dashboard
    return view('dashboard', compact('series'));

})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| JALUR TELEGRAM MAGIC LINK 🚀 (TAMBAHAN BARU)
|--------------------------------------------------------------------------
*/

// 1. Route untuk memproses login saat link diklik
Route::get('/login/magic/{id}', function (Request $request, $id) {
    // Validasi tanda tangan URL (biar gak bisa dipalsukan)
    if (! $request->hasValidSignature()) {
        abort(401, 'Link Login Sudah Kadaluarsa atau Tidak Valid.');
    }

    // Cari user dan login otomatis
    $user = User::findOrFail($id);
    Auth::login($user);

    // Redirect ke dashboard
    return redirect('/dashboard')->with('success', 'Berhasil login via Telegram!');
})->name('login.magic');

// 2. Route TEST untuk kirim link ke Telegram (Buka ini di browser untuk tes)
Route::get('/kirim-magic-link', function () {
    // Ambil user pertama (Biasanya admin/kamu)
    $user = User::first(); 
    
    if(!$user) {
        return "❌ Error: Belum ada user di database! Daftar dulu.";
    }

    // Buat Link Login (Berlaku 10 menit)
    $url = URL::temporarySignedRoute(
        'login.magic', now()->addMinutes(10), ['id' => $user->id]
    );

    // Data dari .env
    $token = env('TELEGRAM_BOT_TOKEN');
    $chatId = env('TELEGRAM_ADMIN_ID');

    // Pesan yang dikirim ke Bot
    $pesan = "🔐 *Magic Link Login*\n\nHalo {$user->name}, klik link di bawah ini untuk masuk ke Dashboard tanpa password:\n\n" . $url;

    // Kirim Request ke Telegram
    $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
        'chat_id' => $chatId,
        'text' => $pesan,
    ]);

    return "✅ Link berhasil dikirim ke Telegram! Cek HP kamu sekarang.";
});

require __DIR__.'/auth.php';