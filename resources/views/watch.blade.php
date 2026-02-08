<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📺 Ruang Nonton
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-900 min-h-screen text-white">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <a href="{{ route('home') }}" class="inline-flex items-center mb-6 text-gray-400 hover:text-white transition group">
                <span class="mr-2 group-hover:-translate-x-1 transition-transform">⬅️</span> Kembali ke Home
            </a>

            <div class="mb-6">
                <h1 class="text-3xl md:text-4xl font-bold text-blue-400 mb-2">{{ $anime->title }}</h1>
                <p class="text-xl text-gray-300">Sedang Menonton: <span class="font-mono text-white font-bold">Episode {{ $episode->episode_number }}</span></p>
            </div>

            @php
                $videoLinks = $episode->video_url;
                
                // Pastikan data diproses sebagai array
                if (is_string($videoLinks)) {
                    $videoLinks = json_decode($videoLinks, true);
                }

                // Reset index agar selalu mulai dari 0 untuk menghindari 'Undefined array key 0'
                if (is_array($videoLinks) && !empty($videoLinks)) {
                    $videoLinks = array_values($videoLinks);
                } else {
                    $videoLinks = [];
                }
            @endphp

            <div class="w-full bg-black rounded-xl overflow-hidden shadow-2xl border border-gray-700 mb-4 relative z-10">
                <div style="position: relative; width: 100%; padding-bottom: 56.25%;"> 
                    @if(isset($videoLinks[0]))
                        <iframe id="mainPlayer" 
                                src="{{ $videoLinks[0] }}" 
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                                frameborder="0" allowfullscreen>
                        </iframe>
                    @else
                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                             class="flex flex-col items-center justify-center text-gray-500 gap-4 bg-gray-800">
                            <span class="text-6xl">⚠️</span>
                            <p class="text-xl font-bold text-red-400">Video Tidak Ditemukan</p>
                            <p class="text-sm">Silakan coba Update Robot kembali.</p>
                        </div>
                    @endif
                </div>
            </div>

            @if(count($videoLinks) > 0)
            <div class="mb-8 bg-gray-800 p-4 rounded-lg border border-gray-700">
                <h3 class="text-sm font-bold text-gray-400 mb-3">📡 PILIH SERVER:</h3>
                <div class="flex flex-wrap gap-3">
                    @foreach($videoLinks as $index => $link)
                        <button onclick="gantiServer('{{ $link }}', this)" 
                                class="server-btn px-5 py-2 rounded-lg text-sm font-bold transition border 
                                {{ $index == 0 ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-700 text-gray-300 border-gray-600' }}">
                            Server {{ $index + 1 }}
                        </button>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="bg-gray-800 p-6 rounded-xl border border-gray-700">
                <h3 class="font-bold text-lg mb-4 text-white border-b border-gray-600 pb-2">📂 Daftar Episode</h3>
                <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-10 gap-2 max-h-[350px] overflow-y-auto custom-scrollbar pr-2">
                    @foreach($allEpisodes as $ep)
                        <a href="{{ route('anime.watch', ['id' => $anime->id, 'ep' => $ep->episode_number]) }}" 
                           class="block text-center py-2 px-1 rounded text-sm transition font-mono
                           {{ $ep->episode_number == $episode->episode_number 
                               ? 'bg-blue-600 text-white font-bold scale-105 ring-1 ring-blue-400' 
                               : 'bg-gray-700 hover:bg-gray-600 text-gray-300' }}">
                            {{ $ep->episode_number }}
                        </a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    <script>
        function gantiServer(url, btn) {
            document.getElementById('mainPlayer').src = url;
            document.querySelectorAll('.server-btn').forEach(b => {
                b.className = "server-btn px-5 py-2 rounded-lg text-sm font-bold transition border bg-gray-700 text-gray-300 border-gray-600";
            });
            btn.className = "server-btn px-5 py-2 rounded-lg text-sm font-bold transition border bg-blue-600 text-white border-blue-500 shadow-lg shadow-blue-900/50";
        }
    </script>
</x-app-layout>