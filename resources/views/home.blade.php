<!DOCTYPE html>
<html lang="id">
<head>
    <title>Anime Stream</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #1a1a1a; color: white; }</style>
</head>
<body>
    <nav class="bg-purple-800 p-4 font-bold text-center text-xl shadow-lg">
        MY ANIME STREAM
    </nav>

    <div class="container mx-auto p-4">
        <h2 class="text-xl font-bold mb-4 border-l-4 border-purple-500 pl-2">Update Terbaru</h2>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($series as $item)
            <div class="bg-gray-800 rounded-lg overflow-hidden hover:scale-105 transition duration-300 shadow-lg group">
                <a href="{{ route('anime.watch', [$item->id, $item->latestEpisode->episode_number ?? 1]) }}">
                    <div class="relative aspect-[2/3]">
                      <img src="{{ asset($item->image_url) }}" class="w-full h-full object-cover group-hover:opacity-80 transition">
                        <span class="absolute top-0 right-0 bg-red-600 text-xs font-bold px-2 py-1 rounded-bl">{{ $item->type }}</span>
                        <div class="absolute bottom-0 w-full bg-gradient-to-t from-black to-transparent p-2 pt-8">
                            <span class="bg-purple-600 text-[10px] px-1 rounded text-white">
                                Ep {{ $item->latestEpisode->episode_number ?? '?' }}
                            </span>
                        </div>
                    </div>
                    <div class="p-2">
                        <h3 class="text-sm font-semibold truncate text-gray-200 group-hover:text-purple-400">{{ $item->title }}</h3>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>