<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🚀 Ruang Kendali Admin
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-600 text-white p-4 rounded-lg mb-6 shadow-lg flex items-center gap-2">
                    ✅ <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-600 text-white p-4 rounded-lg mb-6 shadow-lg flex items-center gap-2">
                    ⚠️ <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="mb-8">
                <div class="flex justify-between items-end mb-2">
                    <h3 class="text-xl text-white font-bold flex items-center gap-2">
                        🔴 Live Activity Log 
                    </h3>
                    <span class="text-xs text-gray-400 animate-pulse">● Auto-refresh: 2s</span>
                </div>
                
                <div class="bg-black text-green-400 p-4 rounded-lg shadow-2xl font-mono text-sm h-64 overflow-y-auto border border-green-800 relative">
                    <div class="absolute inset-0 pointer-events-none bg-[linear-gradient(rgba(18,16,16,0)_50%,rgba(0,0,0,0.25)_50%),linear-gradient(90deg,rgba(255,0,0,0.06),rgba(0,255,0,0.02),rgba(0,0,255,0.06))] z-10 background-size-[100%_2px,3px_100%] pointer-events-none"></div>

                    <div id="log-content" class="space-y-1 relative z-0">
                        <p class="text-gray-500">⏳ Menghubungkan ke satelit robot...</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-8 border border-gray-700">
                <h3 class="text-lg text-white font-bold mb-4">➕ Tambah Anime Baru</h3>
                <form action="{{ route('admin.series.store') }}" method="POST" class="flex flex-col md:flex-row gap-4">
                    @csrf
                    <div class="flex-1">
                        <input type="text" name="title" placeholder="Judul Anime (Contoh: One Piece)" 
                               class="w-full rounded bg-gray-900 text-white border-gray-600 focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>
                    <div class="flex-1">
                        <input type="url" name="source_url" placeholder="Link Anichin (https://anichin...)" 
                               class="w-full rounded bg-gray-900 text-white border-gray-600 focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded shadow-lg transition transform hover:scale-105">
                        Simpan
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h3 class="text-lg font-bold text-white">📋 Daftar Anime ({{ $series->count() }})</h3>
                        
                        <form action="{{ route('admin.scrape.all') }}" method="POST" onsubmit="return confirm('⚠️ PERHATIAN: Jalankan robot untuk SEMUA anime sekaligus? Ini akan memakan RAM cukup besar.')">
                            @csrf
                            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-6 rounded-full shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                                🔥 Update Semua Anime
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-700 text-gray-400 uppercase text-xs tracking-wider">
                                    <th class="p-3">Poster</th>
                                    <th class="p-3">Judul Anime</th>
                                    <th class="p-3">Link Sumber</th>
                                    <th class="p-3">Episode</th>
                                    <th class="p-3 text-right">Aksi Robot</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($series as $anime)
                                <tr class="border-b border-gray-700 hover:bg-gray-700 transition duration-150">
                                    <td class="p-3">
                                        <img src="{{ asset($anime->poster_image) }}" alt="Poster" class="h-12 w-8 object-cover rounded border border-gray-600">
                                    </td>
                                    <td class="p-3 font-bold text-white">{{ $anime->title }}</td>
                                    <td class="p-3">
                                        @if($anime->source_url)
                                            <a href="{{ $anime->source_url }}" target="_blank" class="text-blue-400 hover:underline text-xs block max-w-[200px] truncate">
                                                {{ $anime->source_url }}
                                            </a>
                                        @else
                                            <span class="text-yellow-500 text-xs flex items-center gap-1">⚠️ Belum ada link</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <span class="bg-gray-900 text-blue-200 px-2 py-1 rounded text-xs border border-gray-600">
                                            {{ $anime->episodes_count }} Eps
                                        </span>
                                    </td>
                                    <td class="p-3 flex justify-end items-center gap-2">
                                        <a href="{{ route('admin.series.edit', $anime->id) }}" 
                                           class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-sm transition shadow flex items-center gap-1">
                                            ✏️ Edit
                                        </a>
                                        <form action="{{ route('admin.scrape', $anime->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm transition shadow flex items-center gap-1">
                                                🤖 Update
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach

                                @if($series->isEmpty())
                                    <tr>
                                        <td colspan="5" class="p-6 text-center text-gray-500">Belum ada anime.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const logContainer = document.getElementById('log-content');

        function fetchLogs() {
            fetch("{{ route('admin.logs') }}")
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    if(data.length === 0) {
                        html = '<p class="text-gray-500 italic">Menunggu aktivitas robot...</p>';
                    } else {
                        data.forEach(log => {
                            let date = new Date(log.created_at);
                            let time = date.toLocaleTimeString('id-ID', { hour12: false });
                            let messageClass = 'text-green-400';
                            
                            if (log.message.includes('⚠️') || log.message.includes('❌')) {
                                messageClass = 'text-yellow-400 font-bold';
                            } else if (log.message.includes('🚀') || log.message.includes('🔥')) {
                                messageClass = 'text-blue-400 font-bold border-b border-gray-800 pb-1 mb-1 block';
                            }

                            html += `<div class="font-mono text-xs md:text-sm tracking-wide mb-1">
                                        <span class="text-gray-500 mr-2 opacity-70">[${time}]</span>
                                        <span class="${messageClass}">${log.message}</span>
                                     </div>`;
                        });
                    }
                    logContainer.innerHTML = html;
                })
                .catch(error => console.error('Error fetching logs:', error));
        }

        fetchLogs();
        setInterval(fetchLogs, 2000);
    </script>
</x-app-layout>