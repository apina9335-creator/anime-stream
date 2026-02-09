<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http; // Pakai HTTP Client Ringan
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Series;
use App\Models\Episode;

class GrabAnime extends Command
{
    protected $signature = 'anime:grab {url} {series_id}';
    protected $description = 'Robot Scraper Anime (Versi Ringan Tanpa Browser)';

    public function handle()
    {
        $url = $this->argument('url');
        $seriesId = $this->argument('series_id');

        $anime = Series::find($seriesId);
        if (!$anime) {
            $this->error("❌ Series tidak ditemukan");
            return;
        }

        $this->info("🤖 Mulai scrape: {$anime->title} (Mode Ringan)");

        try {
            // 1. AMBIL SOURCE CODE HALAMAN UTAMA (Tanpa Browser)
            $html = $this->fetchHtml($url);
            
            if (!$html) {
                $this->error("❌ Gagal mengambil halaman utama.");
                return;
            }

            $crawler = new Crawler($html);

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

            // Bersihkan link
            $episodeLinks = array_values(array_unique(array_filter($episodeLinks)));
            $totalEps = count($episodeLinks);
            $this->info("📋 Ditemukan $totalEps episode");

            if ($totalEps == 0) {
                $this->error("❌ 0 Episode ditemukan.");
                return;
            }

            // 2. LOOP EPISODE
            foreach ($episodeLinks as $epUrl) {
                if (!str_contains($epUrl, 'http')) $epUrl = $url . $epUrl;

                preg_match('/episode-(\d+)/', $epUrl, $m);
                $epNum = $m[1] ?? 0;

                if ($epNum == 0) continue;

                // Cek database
                if (Episode::where('series_id', $seriesId)->where('episode_number', $epNum)->exists()) {
                    // $this->line("⏩ Skip Ep $epNum (Sudah ada)");
                    continue; 
                }

                $this->line("⏳ Proses Ep $epNum...");

                // Jeda sedikit biar gak dikira DDOS
                usleep(500000); // 0.5 detik

                // PROSES EPISODE
                $this->processEpisode($epUrl, $seriesId, $epNum);
            }
            $this->info("🏁 SELESAI");

        } catch (\Exception $e) {
            $this->error("❌ Error Fatal: " . $e->getMessage());
        }
    }

    private function processEpisode($url, $seriesId, $epNum)
    {
        $html = $this->fetchHtml($url);
        if (!$html) {
            $this->warn("⚠️ Gagal buka Ep $epNum");
            return;
        }

        $crawler = new Crawler($html);
        $videoData = [];

        // --- TEKNIK CARI VIDEO (Sama tapi tanpa JS) ---

        // Jurus 1: Iframe
        $crawler->filter('iframe')->each(function ($iframe) use (&$videoData) {
            $src = $iframe->attr('src');
            if ($this->isValidVideo($src)) $videoData[$this->detectServerName($src)] = $src;
        });

        // Jurus 2: Dropdown/Base64
        $crawler->filter('select option')->each(function ($opt) use (&$videoData) {
            $val = $opt->attr('value');
            $link = $val;
            
            // Dekode Base64
            if ($val && !str_contains($val, 'http')) {
                $decoded = base64_decode($val, true);
                if ($decoded && preg_match('/src="([^"]+)"/', $decoded, $matches)) $link = $matches[1];
            }
            
            if ($this->isValidVideo($link)) $videoData[$this->cleanServerName($opt->text())] = $link;
        });

        // Jurus 3: Regex (Paling Ampuh di Mode Tanpa Browser)
        // Kita cari string URL di dalam script mentah
        $patterns = ['ok.ru', 'blogger.com', 'dailymotion', 'drive.google', 'pixeldrain', 'streamtape', 'mp4upload', 'hxfile', 'dood', 'filelions', 'pahe.win', 'streamwish'];
        foreach ($patterns as $p) {
            // Regex mencari link di dalam source code
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
            $this->warn("⚠️ Ep $epNum kosong (Mungkin butuh JS/Login).");
        }
    }

    // --- FUNGSI REQUEST RINGAN ---
    private function fetchHtml($url)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://google.com'
            ])->timeout(15)->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
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