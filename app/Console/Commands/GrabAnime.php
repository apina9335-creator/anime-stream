<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Series;
use App\Models\Episode;
use App\Models\ScraperLog;

class GrabAnime extends Command
{
    protected $signature = 'anime:grab {url} {series_id}';
    protected $description = 'Robot Scraper Anime (Fixed Selector & Base64 Support)';

    public function handle()
    {
        $url = $this->argument('url');
        $seriesId = $this->argument('series_id');

        $anime = Series::find($seriesId);
        if (!$anime) {
            $this->error("❌ Series tidak ditemukan");
            return;
        }

        $this->info("🤖 Mulai scrape: {$anime->title}");
        
        // ===== SETUP CHROME =====
        $driverPath = PHP_OS_FAMILY === 'Windows' ? base_path('chromedriver.exe') : null;

        // Opsi Chrome
        $client = Client::createChromeClient($driverPath, [
            // '--headless', // Aktifkan ini kalau sudah stabil (biar gak muncul window)
            '--no-sandbox',
            '--disable-gpu',
            '--window-size=1200,1100',
            '--disable-popup-blocking',
            '--ignore-certificate-errors',
            '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36'
        ]);

        try {
            // ===== BUKA HALAMAN & CEK REDIRECT =====
            $this->safeOpen($client, $url);
            
            // Snapshot halaman agar kebal refresh/iklan
            $this->line("📸 Mengambil snapshot halaman...");
            $html = $client->getPageSource(); 
            $crawler = new Crawler($html);

            // ===== CARI LINK EPISODE (DENGAN SELECTOR BARU) =====
            $this->line("🔍 Mencari daftar episode...");
            
            // DAFTAR JURUS PENCARIAN (SAYA TAMBAH .episodelist DI SINI)
            $selectors = [
                '.episodelist ul li a', // <--- INI YANG BARU (Sesuai HTML kamu)
                '.eplister ul li a',    // Anichin lama
                '.lstep ul li a',       // Varian lain
                '#chapterlist ul li a', 
                '.listsb ul li a',
                '.bxcl ul li a'         // Cadangan
            ];

            $episodeLinks = [];

            foreach ($selectors as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $this->info("✅ Ketemu pakai jurus: $selector");
                    $episodeLinks = $crawler->filter($selector)->each(function ($node) {
                        return $node->attr('href');
                    });
                    break;
                }
            }

            // Fallback: Cari link manual yang ada kata "episode"
            if (empty($episodeLinks)) {
                $episodeLinks = $crawler->filter('a')->each(function ($node) {
                    $href = $node->attr('href');
                    return ($href && str_contains($href, 'episode')) ? $href : null;
                });
            }

            // Bersihkan Data
            $episodeLinks = array_values(array_unique(array_filter($episodeLinks)));
            $totalEps = count($episodeLinks);
            $this->info("📋 Ditemukan $totalEps episode");

            if ($totalEps == 0) {
                $this->error("❌ Masih 0 Episode. Pastikan link series benar (bukan link home).");
                return;
            }

            // ===== LOOP EPISODE =====
            // Kita balik urutannya biar dari Episode 1 (Opsional, hapus array_reverse kalau mau dari terbaru)
            // $episodeLinks = array_reverse($episodeLinks); 

            foreach ($episodeLinks as $epUrl) {
                // Perbaiki URL jika relatif
                if (!str_contains($epUrl, 'http')) $epUrl = $url . $epUrl;

                // Ambil nomor episode
                preg_match('/episode-(\d+)/', $epUrl, $m);
                $epNum = $m[1] ?? 0;
                if ($epNum == 0) continue; // Skip kalau gak ada nomornya

                // Cek database, kalau sudah ada skip biar cepat
                if (Episode::where('series_id', $seriesId)->where('episode_number', $epNum)->exists()) {
                    // $this->line("⏩ Skip Ep $epNum (Sudah ada)");
                    continue; 
                }

                $this->line("⏳ Proses Ep $epNum...");
                
                // Buka Halaman Episode
                $this->safeOpen($client, $epUrl);
                $epHtml = $client->getPageSource();
                $epCrawler = new Crawler($epHtml);
                $videoData = [];

                // --- JURUS 1: IFRAME LANGSUNG ---
                $epCrawler->filter('iframe')->each(function ($iframe) use (&$videoData) {
                    $src = $iframe->attr('src');
                    if (isValidVideo($src)) $videoData[detectServerName($src)] = $src;
                });

                // --- JURUS 2: DROPDOWN & BASE64 (Sesuai HTML Kamu) ---
                $epCrawler->filter('select option')->each(function ($opt) use (&$videoData) {
                    $val = $opt->attr('value');
                    $link = $val;

                    // Kalau value-nya aneh (Base64), kita decode dulu
                    if ($val && !str_contains($val, 'http')) {
                        $decoded = base64_decode($val, true);
                        // Hasil decode biasanya: <iframe src="...">
                        if ($decoded && preg_match('/src="([^"]+)"/', $decoded, $matches)) {
                            $link = $matches[1];
                        }
                    }
                    
                    if (isValidVideo($link)) {
                        $videoData[cleanServerName($opt->text())] = $link;
                    }
                });

                // --- JURUS 3: REGEX PENCARI URL ---
                $patterns = ['ok.ru', 'blogger.com', 'dailymotion', 'drive.google', 'pixeldrain', 'streamtape', 'mp4upload', 'hxfile', 'dood', 'filelions', 'pahe.win', 'streamwish'];
                foreach ($patterns as $p) {
                    preg_match_all('/https?:\/\/[^"\']*' . preg_quote($p, '/') . '[^"\']*/i', $epHtml, $m);
                    foreach ($m[0] ?? [] as $raw) {
                        $clean = str_replace(['\\', '"', "'"], '', $raw);
                        if (isValidVideo($clean)) $videoData[detectServerName($clean)] = $clean;
                    }
                }

                // SIMPAN KE DATABASE
                if ($videoData) {
                    Episode::updateOrCreate(
                        ['series_id' => $seriesId, 'episode_number' => $epNum],
                        ['video_url' => $videoData, 'updated_at' => now()]
                    );
                    $this->info("✅ Ep $epNum tersimpan (" . count($videoData) . " server)");
                } else {
                    $this->warn("⚠️ Ep $epNum kosong (Gagal ambil video)");
                }
            }
            $this->info("🏁 SELESAI");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        } finally {
            $client->quit();
        }
    }

    // --- FUNGSI PENGAMAN ---
    private function safeOpen($client, $targetUrl)
    {
        $client->request('GET', $targetUrl);
        sleep(5); // Tunggu loading

        // Cek halaman "7 detik" / Ruang Tunggu
        $html = $client->getPageSource();
        if (str_contains($html, 'otomatis di alihkan') || str_contains($html, '7 detik') || str_contains($html, 'Klik Menuju')) {
            $this->warn("✋ Terdeteksi Halaman Ruang Tunggu!");
            try {
                $link = $client->getCrawler()->selectLink('Klik Menuju Web Anichin');
                if ($link->count() > 0) {
                    $client->click($link->link());
                    sleep(5);
                } else {
                    $this->info("💤 Menunggu redirect otomatis (15 detik)...");
                    sleep(15);
                }
            } catch (\Exception $e) {
                sleep(15);
            }
        }
    }
}

// --- HELPER FUNCTIONS ---
function isValidVideo($url) {
    if (!$url || !str_contains($url, 'http')) return false;
    if (preg_match('/\.(jpg|png|gif|css|js|svg)$/', $url)) return false;
    return true;
}
function detectServerName($url) {
    $host = parse_url($url, PHP_URL_HOST) ?? 'unknown';
    return strtoupper(explode('.', $host)[0]);
}
function cleanServerName($name) {
    return trim(str_replace(['[ADS]', 'Server', '-'], '', $name));
}