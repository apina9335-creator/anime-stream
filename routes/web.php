<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnimeController;
use App\Http\Controllers\AdminController;
use App\Models\Series; // <--- 1. INI WAJIB DITAMBAHKAN
use Illuminate\Support\Facades\Route;

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
    // <--- 2. LOGIKA BARU DIMULAI DI SINI
    // Ambil data anime dari database, urutkan dari yang terbaru, 20 per halaman
    $series = Series::orderBy('updated_at', 'desc')->paginate(20);
    
    // Kirim data ($series) ke tampilan dashboard
    return view('dashboard', compact('series'));
    // <--- LOGIKA BARU SELESAI

})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';