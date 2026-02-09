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
    protected $description = 'Robot Scraper Anime (Anti-Crash & Base64 Support)';

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
        
        // ===== SETUP CHROME (SETTINGAN ANTI-CRASH) =====
        $driverPath = PHP_OS_FAMILY === 'Windows' ? base_path('chromedriver.exe') : null;

        $client = Client::createChromeClient($driverPath, [
            '--headless=new',           // Wajib: Jalan tanpa layar
            '--no-sandbox',             // Wajib: Aman di container
            '--disable-gpu',            // Hemat RAM
            '--disable-dev-shm-usage',  // OBAT CRASH NO. 1 (PENTING!)
            '--disable-extensions',     // Matikan ekstensi berat
            '--mute-audio',             // Gak usah load suara
            '--disable-software-rasterizer',
            '--window-size=1920,1080',  // Ukuran standar
            '--disable-popup-blocking',
            '--ignore-certificate-errors',
            '--user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ]);

        try {
            // ===== BUKA HALAMAN UTAMA =====
            $this->safeOpen($client, $url);
            
            $this->line("📸 Mengambil snapshot halaman...");
            $html = $client->getPageSource(); 
            $crawler = new Crawler($html);

            // ===== CARI DAFTAR EPISODE =====
            $this->line("🔍 Mencari daftar episode...");
            
            // Daftar selector yang mungkin dipakai web target
            $selectors = [
                '.episodelist ul li a', 
                '.eplister ul li a',    
                '.lstep ul li a',       
                '#chapterlist ul li a', 
                '.listsb ul li a',
                '.bxcl ul li a'         
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

            // Fallback manual jika selector gagal
            if (empty($episodeLinks)) {
                $episodeLinks = $crawler->filter('a')->each(function ($node) {
                    $href = $node->attr('href');
                    return ($href && str_contains($href, 'episode')) ? $href : null;
                });
            }

            // Bersihkan duplikat
            $episodeLinks = array_values(array_unique(array_filter($episodeLinks)));
            $totalEps = count($episodeLinks);
            $this->info("📋 Ditemukan $totalEps episode");

            if ($totalEps == 0) {
                $this->error("❌ Masih 0 Episode. Pastikan link series benar.");
                return;
            }

            // ===== LOOP EPISODE =====
            // $episodeLinks = array_reverse($episodeLinks); // Aktifkan jika ingin urut dari Ep 1

            foreach ($episodeLinks as $epUrl) {
                // Perbaiki URL jika relatif
                if (!str_contains($epUrl, 'http')) $epUrl = $url . $epUrl;

                // Ambil nomor episode dari URL
                preg_match('/episode-(\d+)/', $epUrl, $m);
                $epNum = $m[1] ?? 0;
                if ($epNum == 0) continue; 

                // Cek database, kalau sudah ada skip biar cepat
                if (Episode::where('series_id', $seriesId)->where('episode_number', $epNum)->exists()) {
                    // $this->line("⏩ Skip Ep $epNum (Sudah ada)");
                    continue; 
                }

                // === PENTING: JEDA ISTIRAHAT BIAR GAK CRASH ===
                sleep(2); 
                // =============================================

                $this->line("⏳ Proses Ep $epNum...");
                
                // Buka Halaman Episode
                $this->safeOpen($client, $epUrl);
                $epHtml = $client->getPageSource();
                $epCrawler = new Crawler($epHtml);
                $videoData = [];

                // Jurus 1: Iframe Langsung
                $epCrawler->filter('iframe')->each(function ($iframe) use (&$videoData) {
                    $src = $iframe->attr('src');
                    if (isValidVideo($src)) $videoData[detectServerName($src)] = $src;
                });

                // Jurus 2: Dropdown & Base64
                $epCrawler->filter('select option')->each(function ($opt) use (&$videoData) {
                    $val = $opt->attr('value');
                    $link = $val;

                    if ($val && !str_contains($val, 'http')) {
                        $decoded = base64_decode($val, true);
                        if ($decoded && preg_match('/src="([^"]+)"/', $decoded, $matches)) {
                            $link = $matches[1];
                        }
                    }
                    
                    if (isValidVideo($link)) {
                        $videoData[cleanServerName($opt->text())] = $link;
                    }
                });

                // Jurus 3: Regex Pencari URL
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
        try {
            $client->request('GET', $targetUrl);
            sleep(3); // Tunggu loading awal

            $html = $client->getPageSource();
            if (str_contains($html, 'otomatis di alihkan') || str_contains($html, '7 detik') || str_contains($html, 'Klik Menuju')) {
                $this->warn("✋ Terdeteksi Ruang Tunggu, mencoba bypass...");
                try {
                    $link = $client->getCrawler()->selectLink('Klik Menuju Web Anichin');
                    if ($link->count() > 0) {
                        $client->click($link->link());
                        sleep(5);
                    } else {
                        sleep(10); // Tunggu redirect otomatis
                    }
                } catch (\Exception $e) {
                    sleep(10);
                }
            }
        } catch (\Exception $e) {
            $this->warn("⚠️ Gagal buka link, mencoba lanjut...");
        }
    }
}

// --- HELPER FUNCTIONS (Di Luar Class) ---
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