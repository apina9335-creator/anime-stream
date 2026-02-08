<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Anime Stream</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">

    <nav class="bg-purple-800 p-4 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <a href="{{ route('home') }}" class="text-2xl font-bold flex items-center gap-2">
                🎬 MY ANIME STREAM
            </a>
            <input type="text" placeholder="Cari anime..." class="bg-gray-800 px-4 py-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
    </nav>

    <div class="bg-gray-800 py-12 mb-8">
        <div class="container mx-auto text-center px-4">
            <h1 class="text-4xl font-bold mb-2">Selamat Datang Wibu! 🌟</h1>
            <p class="text-gray-400">Nonton anime gratis tanpa iklan judi slot.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 pb-12">
        <h2 class="text-xl font-bold mb-6 border-l-4 border-purple-500 pl-3">Update Terbaru</h2>

        @if($series->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                @foreach($series as $anime)
                    <a href="{{ route('anime.watch', [$anime->id, 1]) }}" class="group relative block bg-gray-800 rounded-xl overflow-hidden shadow-lg hover:shadow-purple-500/50 transition duration-300 transform hover:-translate-y-2">
                        <div class="aspect-[2/3] w-full relative">
                            <img src="{{ $anime->image_url }}" alt="{{ $anime->title }}" class="absolute inset-0 w-full h-full object-cover group-hover:opacity-80 transition">
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300">
                                <div class="bg-purple-600 rounded-full p-3 shadow-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-gray-800">
                            <h3 class="text-sm font-bold truncate group-hover:text-purple-400">{{ $anime->title }}</h3>
                            <p class="text-xs text-gray-400 mt-1">{{ $anime->type }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-20 text-gray-500">
                <p class="text-xl">Belum ada anime yang ditambahkan.</p>
                <p class="text-sm mt-2">Jalankan robot untuk mengisi konten.</p>
            </div>
        @endif
    </div>

</body>
</html>