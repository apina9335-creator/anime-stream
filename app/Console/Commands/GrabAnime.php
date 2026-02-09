<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Series;
use App\Models\Episode;
use Facebook\WebDriver\Exception\WebDriverException;

class GrabAnime extends Command
{
    protected $signature = 'anime:grab {url} {series_id}';
    protected $description = 'Robot Scraper Anime (Anti-Crash, Auto-Restart & Memory Saver)';

    private $client = null;

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
        
        // 1. MULAI BROWSER PERTAMA KALI
        $this->startBrowser();

        try {
            // 2. AMBIL DAFTAR EPISODE
            $this->safeOpen($url);
            $crawler = new Crawler($this->client->getPageSource());

            $this->line("🔍 Mencari daftar episode...");
            $selectors = ['.episodelist ul li a', '.eplister ul li a', '.lstep ul li a', '#chapterlist ul li a', '.listsb ul li a', '.bxcl ul li a'];
            $episodeLinks = [];

            foreach ($selectors as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $this->info("✅ Ketemu pakai jurus: $selector");
                    $episodeLinks = $crawler->filter($selector)->each(fn($n) => $n->attr('href'));
                    break;
                }
            }

            if (empty($episodeLinks)) {
                $episodeLinks = $crawler->filter('a')->each(function ($node) {
                    $href = $node->attr('href');
                    return ($href && str_contains($href, 'episode')) ? $href : null;
                });
            }

            $episodeLinks = array_values(array_unique(array_filter($episodeLinks)));
            $totalEps = count($episodeLinks);
            $this->info("📋 Ditemukan $totalEps episode");

            if ($totalEps == 0) {
                $this->error("❌ 0 Episode ditemukan. Cek linknya.");
                return;
            }

            // 3. LOOP EPISODE DENGAN SISTEM ANTI-CRASH
            $processedCount = 0;

            foreach ($episodeLinks as $epUrl) {
                if (!str_contains($epUrl, 'http')) $epUrl = $url . $epUrl;

                // Ambil nomor episode
                preg_match('/episode-(\d+)/', $epUrl, $m);
                $epNum = $m[1] ?? 0;
                if ($epNum == 0) continue;

                // Cek database (Skip jika ada)
                if (Episode::where('series_id', $seriesId)->where('episode_number', $epNum)->exists()) {
                    continue; 
                }

                // --- MEMORY SAVER: RESTART BROWSER TIAP 5 EPISODE ---
                $processedCount++;
                if ($processedCount % 5 === 0) {
                    $this->warn("♻️ Membersihkan Memori (Restart Browser)...");
                    $this->restartBrowser();
                }

                $this->line("⏳ Proses Ep $epNum...");

                // --- SMART RETRY SYSTEM ---
                $retry = 0;
                $maxRetries = 2; // Kesempatan 2x kalau gagal
                $success = false;

                while ($retry < $maxRetries && !$success) {
                    try {
                        $this->processEpisode($epUrl, $seriesId, $epNum);
                        $success = true; // Berhasil!
                    } catch (\Exception $e) {
                        $msg = $e->getMessage();
                        
                        // DETEKSI CRASH / INVALID SESSION
                        if (str_contains($msg, 'invalid session') || str_contains($msg, 'died') || str_contains($msg, 'chrome not reachable')) {
                            $this->error("💥 Browser CRASH di Ep $epNum! (Percobaan " . ($retry+1) . ")");
                            $this->restartBrowser(); // Hidupkan lagi browsernya
                            $retry++;
                        } else {
                            $this->warn("⚠️ Gagal Ep $epNum: $msg");
                            break; // Error lain (bukan crash), skip aja
                        }
                    }
                }
            }
            $this->info("🏁 SELESAI");

        } catch (\Exception $e) {
            $this->error("❌ Error Fatal: " . $e->getMessage());
        } finally {
            $this->closeBrowser();
        }
    }

    // --- FUNGSI PROSES 1 EPISODE ---
    private function processEpisode($url, $seriesId, $epNum)
    {
        $this->safeOpen($url);
        
        $html = $this->client->getPageSource();
        $crawler = new Crawler($html);
        $videoData = [];

        // Jurus 1: Iframe
        $crawler->filter('iframe')->each(function ($iframe) use (&$videoData) {
            $src = $iframe->attr('src');
            if ($this->isValidVideo($src)) $videoData[$this->detectServerName($src)] = $src;
        });

        // Jurus 2: Dropdown/Base64
        $crawler->filter('select option')->each(function ($opt) use (&$videoData) {
            $val = $opt->attr('value');
            $link = $val;
            if ($val && !str_contains($val, 'http')) {
                $decoded = base64_decode($val, true);
                if ($decoded && preg_match('/src="([^"]+)"/', $decoded, $matches)) $link = $matches[1];
            }
            if ($this->isValidVideo($link)) $videoData[$this->cleanServerName($opt->text())] = $link;
        });

        // Jurus 3: Regex
        $patterns = ['ok.ru', 'blogger.com', 'dailymotion', 'drive.google', 'pixeldrain', 'streamtape', 'mp4upload', 'hxfile', 'dood', 'filelions', 'pahe.win', 'streamwish'];
        foreach ($patterns as $p) {
            preg_match_all('/https?:\/\/[^"\']*' . preg_quote($p, '/') . '[^"\']*/i', $html, $m);
            foreach ($m[0] ?? [] as $raw) {
                $clean = str_replace(['\\', '"', "'"], '', $raw);
                if ($this->isValidVideo($clean)) $videoData[$this->detectServerName($clean)] = $clean;
            }
        }

        if ($videoData) {
            Episode::updateOrCreate(
                ['series_id' => $seriesId, 'episode_number' => $epNum],
                ['video_url' => $videoData, 'updated_at' => now()]
            );
            $this->info("✅ Ep $epNum tersimpan (" . count($videoData) . " server)");
        } else {
            $this->warn("⚠️ Ep $epNum kosong.");
        }
    }

    // --- MANAJEMEN BROWSER ---
    private function startBrowser()
    {
        $driverPath = PHP_OS_FAMILY === 'Windows' ? base_path('chromedriver.exe') : null;
        $this->client = Client::createChromeClient($driverPath, [
            '--headless=new',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage', // KUNCI ANTI CRASH
            '--disable-extensions',
            '--mute-audio',
            '--disable-software-rasterizer',
            '--window-size=1280,720', // Ukuran kecil biar hemat RAM
            '--disable-popup-blocking',
            '--ignore-certificate-errors',
            '--user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ]);
    }

    private function closeBrowser()
    {
        if ($this->client) {
            try {
                $this->client->quit();
            } catch (\Exception $e) {}
            $this->client = null;
        }
    }

    private function restartBrowser()
    {
        $this->closeBrowser();
        sleep(2); // Istirahat sejenak
        $this->startBrowser();
    }

    private function safeOpen($url)
    {
        $this->client->request('GET', $url);
        // Cek redirect aneh
        $html = $this->client->getPageSource();
        if (str_contains($html, 'otomatis di alihkan') || str_contains($html, '7 detik')) {
            sleep(5); // Tunggu redirect
        }
    }

    // --- HELPER ---
    private function isValidVideo($url) {
        return $url && str_contains($url, 'http') && !preg_match('/\.(jpg|png|gif|css|js)$/', $url);
    }
    private function detectServerName($url) {
        return strtoupper(explode('.', parse_url($url, PHP_URL_HOST) ?? 'unknown')[0]);
    }
    private function cleanServerName($name) {
        return trim(str_replace(['[ADS]', 'Server', '-'], '', $name));
    }
}