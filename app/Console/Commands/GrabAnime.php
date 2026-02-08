<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Panther\Client;
use App\Models\Series;
use App\Models\Episode;
use App\Models\ScraperLog;

class GrabAnime extends Command
{
    protected $signature = 'anime:grab {url} {series_id}';
    protected $description = 'Robot Scraper Anime (Final Boss Multi Server)';

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

        if (class_exists(ScraperLog::class)) {
            ScraperLog::create(['message' => "Start scrape {$anime->title}"]);
        }

        // ===== SETUP DRIVER =====
        $driverPath = PHP_OS_FAMILY === 'Windows'
            ? base_path('chromedriver.exe')
            : null;

        $client = Client::createChromeClient($driverPath, [
            '--headless',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--window-size=1200,1100',
        ]);

        try {
            // ===== BUKA HALAMAN SERIES =====
            $crawler = $client->request('GET', $url);
            sleep(2);

            // ===== AMBIL LINK EPISODE =====
            $links = [];

            if ($crawler->filter('.eplister ul li a')->count()) {
                $links = $crawler->filter('.eplister ul li a')->links();
            } else {
                foreach ($crawler->filter('a')->links() as $l) {
                    if (str_contains($l->getUri(), 'episode')) {
                        $links[] = $l;
                    }
                }
            }

            $episodeLinks = [];
            foreach ($links as $l) {
                $episodeLinks[] = $l->getUri();
            }

            $episodeLinks = array_reverse(array_unique($episodeLinks));
            $this->info("📋 Ditemukan " . count($episodeLinks) . " episode");

            // ===== LOOP EPISODE =====
            foreach ($episodeLinks as $i => $epUrl) {

                preg_match('/episode-(\d+)/', $epUrl, $m);
                $epNum = $m[1] ?? ($i + 1);

                if (Episode::where('series_id', $seriesId)
                    ->where('episode_number', $epNum)->exists()) {
                    continue;
                }

                $this->line("⏳ Episode $epNum");

                $page = $client->request('GET', $epUrl);
                sleep(2);

                $html = $page->html();
                $videoData = [];

                /*
                |--------------------------------------------------------------------------
                | 1️⃣ IFRAME
                |--------------------------------------------------------------------------
                */
                $page->filter('iframe')->each(function ($iframe) use (&$videoData) {
                    $src = $iframe->attr('src');
                    if (isValidVideo($src)) {
                        $name = detectServerName($src);
                        $videoData[$name] = $src;
                    }
                });

                /*
                |--------------------------------------------------------------------------
                | 2️⃣ DROPDOWN SERVER (BASE64 / URL)
                |--------------------------------------------------------------------------
                */
                $page->filter('select option')->each(function ($opt) use (&$videoData) {
                    $name = cleanServerName($opt->text());
                    $value = $opt->attr('value');
                    $link = null;

                    if (str_contains($value, 'http')) {
                        $link = $value;
                    } else {
                        $decode = base64_decode($value, true);
                        if ($decode && str_contains($decode, 'http')) {
                            if (preg_match('/src="([^"]+)"/', $decode, $m)) {
                                $link = $m[1];
                            } else {
                                $link = $decode;
                            }
                        }
                    }

                    if ($link && isValidVideo($link)) {
                        if (isset($videoData[$name])) $name .= ' (Alt)';
                        $videoData[$name] = $link;
                    }
                });

                /*
                |--------------------------------------------------------------------------
                | 3️⃣ REGEX HUNTER (JS / HIDDEN)
                |--------------------------------------------------------------------------
                */
                $patterns = [
                    'ok.ru/videoembed',
                    'blogger.com/video-play',
                    'dailymotion.com/embed',
                    'drive.google.com',
                    'pixeldrain.com',
                    'streamtape.com',
                    'mp4upload.com',
                    'hxfile.co',
                    'dood.'
                ];

                foreach ($patterns as $p) {
                    preg_match_all('/https?:\/\/[^"\']*' . preg_quote($p, '/') . '[^"\']*/i', $html, $m);
                    foreach ($m[0] ?? [] as $raw) {
                        $clean = str_replace('\\', '', $raw);
                        if (isValidVideo($clean)) {
                            $name = detectServerName($clean);
                            if (!isset($videoData[$name])) {
                                $videoData[$name] = $clean;
                            }
                        }
                    }
                }

                // ===== SIMPAN =====
                if ($videoData) {
                    Episode::updateOrCreate(
                        ['series_id' => $seriesId, 'episode_number' => $epNum],
                        ['video_url' => $videoData, 'updated_at' => now()]
                    );

                    $msg = "✅ Ep $epNum: " . count($videoData) . " server";
                    $this->info($msg);

                    if (class_exists(ScraperLog::class)) {
                        ScraperLog::create(['message' => $msg]);
                    }
                } else {
                    $this->warn("⚠️ Ep $epNum kosong");
                }
            }

            $this->info("🏁 SELESAI TOTAL");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        } finally {
            $client->quit();
        }
    }
}

/*
|--------------------------------------------------------------------------
| 🔧 HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function isValidVideo($url)
{
    if (!$url || !str_contains($url, 'http')) return false;
    if (preg_match('/\.(jpg|png|gif|css|js|svg)$/', $url)) return false;

    $blacklist = ['facebook.com', 'twitter.com', 'googlesyndication', 'analytics'];
    foreach ($blacklist as $b) {
        if (str_contains($url, $b)) return false;
    }
    return true;
}

function detectServerName($url)
{
    $host = parse_url($url, PHP_URL_HOST) ?? 'unknown';

    $map = [
        'ok.ru' => 'OKRU',
        'blogger.com' => 'Blogger',
        'dailymotion.com' => 'Dailymotion',
        'drive.google.com' => 'GDrive',
        'pixeldrain.com' => 'Pixeldrain',
        'streamtape.com' => 'Streamtape',
        'mp4upload.com' => 'Mp4Upload',
        'hxfile.co' => 'HxFile',
        'dood.' => 'Doodstream',
    ];

    foreach ($map as $k => $v) {
        if (str_contains($host, $k)) return $v;
    }

    return strtoupper(explode('.', $host)[0]);
}

function cleanServerName($name)
{
    return trim(str_replace(
        ['[ADS]', 'Server', 'Pilih', 'Option'],
        '',
        $name
    ));
}
