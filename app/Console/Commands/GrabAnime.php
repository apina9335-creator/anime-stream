<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Series;
use App\Models\Episode;

class GrabAnime extends Command
{
    protected $signature = 'anime:grab {url} {series_id}';
    protected $description = 'Robot Scraper Anime (Ultra Low Memory Mode)';

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
        
        // 1. NYALAKAN BROWSER MODE HEMAT
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

            // 3. LOOP EPISODE
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

                // --- MEMORY SAVER EKSTREM: RESTART BROWSER TIAP 3 EPISODE ---
                // Biar RAM selalu fresh dan tidak numpuk sampah
                $processedCount++;
                if ($processedCount % 3 === 0) {
                    $this->warn("♻️ Cuci Gudang Memori (Restart Browser)...");
                    $this->restartBrowser();
                }

                $this->line("⏳ Proses Ep $epNum...");

                // Retry System
                $retry = 0;
                $maxRetries = 2;
                $success = false;

                while ($retry < $maxRetries && !$success) {
                    try {
                        $this->processEpisode($epUrl, $seriesId, $epNum);
                        $success = true; 
                    } catch (\Exception $e) {
                        $msg = $e->getMessage();
                        // Kalau crash, restart browser dan coba lagi
                        if (str_contains($msg, 'invalid session') || str_contains($msg, 'crash') || str_contains($msg, 'reachable')) {
                            $this->error("💥 Browser CRASH di Ep $epNum! (Restarting...)");
                            $this->restartBrowser();
                            $retry++;
                        } else {
                            $this->warn("⚠️ Gagal Ep $epNum: $msg");
                            break; 
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

    // --- SETTINGAN BROWSER ANTI-JEBOL ---
    private function startBrowser()
    {
        $driverPath = PHP_OS_FAMILY === 'Windows' ? base_path('chromedriver.exe') : null;
        
        $this->client = Client::createChromeClient($driverPath, [
            '--headless=new',           // Wajib headless
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',  // Wajib buat Docker/Railway
            
            // === FITUR HEMAT MEMORI ===
            '--blink-settings=imagesEnabled=false', // JANGAN LOAD GAMBAR (Penting!)
            '--disable-images',                     // Double kill gambar
            '--disable-extensions',                 // Matikan ekstensi
            '--disable-default-apps',
            '--disable-component-extensions-with-background-pages',
            '--mute-audio',                         // Matikan suara
            '--no-first-run',
            '--disable-background-networking',
            
            // Window kecil aja biar gak berat render pixelnya
            '--window-size=800,600', 
            
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
        sleep(2); 
        $this->startBrowser();
    }

    private function safeOpen($url)
    {
        // Set timeout loading biar gak nunggu loading selamanya
        // Sayangnya Panther agak terbatas soal timeout, jadi kita andalkan sleep
        try {
            $this->client->request('GET', $url);
        } catch (\Exception $e) {
            // Abaikan error timeout, lanjut ambil source code yg ada
        }
        
        // Cek redirect
        try {
            $html = $this->client->getPageSource();
            if (str_contains($html, 'otomatis di alihkan') || str_contains($html, '7 detik')) {
                sleep(5); 
            }
        } catch (\Exception $e) {}
    }

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