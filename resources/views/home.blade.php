<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anime Stream - Nonton Anime Sub Indo</title>
    
    {{-- TAILWIND CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- SWIPER CSS (Untuk Slider) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    {{-- FONT AWESOME (Ikon) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    {{-- GOOGLE ADSENSE --}}
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4882375718172598"
     crossorigin="anonymous"></script>

    <style>
        /* Konfigurasi Warna Anichin */
        :root {
            --primary: #dd4e36; /* Merah Anichin */
            --dark-bg: #141414;
            --dark-el: #1f1f1f;
            --text-main: #e5e5e5;
            --text-dim: #a1a1a1;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--dark-bg); }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 4px; }

        /* Utilities */
        .hover-scale:hover { transform: scale(1.02); }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>

    {{-- 1. NAVBAR (HEADER) --}}
    <nav class="bg-[#1f1f1f] border-b border-[#333] sticky top-0 z-50 shadow-lg font-sans">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            
            {{-- KIRI: LOGO & TOMBOL MENU HP --}}
            <div class="flex items-center gap-4">
                {{-- Tombol Hamburger (Hanya Muncul di HP) --}}
                <button id="mobile-menu-btn" class="md:hidden text-gray-300 hover:text-[#dd4e36] text-xl focus:outline-none transition">
                    <i class="fas fa-bars"></i>
                </button>
                
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                    <div class="w-9 h-9 bg-[#dd4e36] rounded flex items-center justify-center font-extrabold text-white text-lg shadow-md group-hover:scale-110 transition duration-300">
                        A
                    </div>
                    <div class="flex flex-col justify-center">
                        <span class="text-lg font-bold tracking-wide text-white leading-none">
                            ANIME<span class="text-[#dd4e36]">STREAM</span>
                        </span>
                        <span class="text-[9px] text-gray-400 font-medium tracking-[0.2em] leading-none mt-0.5">
                            INDONESIA
                        </span>
                    </div>
                </a>
            </div>

            {{-- TENGAH: MENU DESKTOP (Hilang di HP) --}}
            <div class="hidden md:flex items-center gap-1">
                <a href="{{ route('home') }}" class="px-4 py-2 text-sm font-bold text-white bg-[#2a2a2a] rounded-full transition shadow-inner">
                    Home
                </a>
                <a href="{{ route('anime.donghua') }}" class="px-4 py-2 text-sm font-bold text-gray-400 hover:text-white hover:bg-[#2a2a2a] rounded-full transition">
                    Donghua
                </a>
                <a href="{{ route('anime.ongoing') }}" class="px-4 py-2 text-sm font-bold text-gray-400 hover:text-white hover:bg-[#2a2a2a] rounded-full transition">
                    Ongoing
                </a>
                <a href="{{ route('anime.completed') }}" class="px-4 py-2 text-sm font-bold text-gray-400 hover:text-white hover:bg-[#2a2a2a] rounded-full transition">
                    Completed
                </a>
            </div>

            {{-- KANAN: SEARCH & USER --}}
            <div class="flex items-center gap-3">
                {{-- Search Desktop --}}
                <div class="relative hidden md:block group">
                    <form action="{{ route('anime.search') }}" method="GET">
                        <input type="text" name="s" placeholder="Cari anime..." 
                            class="bg-[#141414] text-sm pl-4 pr-10 py-2 rounded-full w-48 focus:w-64 border border-[#333] focus:border-[#dd4e36] focus:outline-none transition-all duration-300 text-gray-300 placeholder-gray-600">
                        <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 group-hover:text-[#dd4e36] transition">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                {{-- Search Icon Mobile (Hanya Ikon) --}}
                <button class="md:hidden text-gray-300 hover:text-[#dd4e36] p-2">
                    <i class="fas fa-search"></i>
                </button>

                {{-- User Icon --}}
                <a href="#" class="w-9 h-9 rounded-full bg-[#2a2a2a] flex items-center justify-center text-gray-400 hover:bg-[#dd4e36] hover:text-white transition shadow-md border border-[#333]">
                    <i class="fas fa-user text-sm"></i>
                </a>
            </div>
        </div>

        {{-- MENU DROPDOWN HP (Hidden by default) --}}
        <div id="mobile-menu" class="hidden md:hidden bg-[#1f1f1f] border-t border-[#333] absolute w-full left-0 top-16 shadow-2xl transition-all duration-300 z-40">
            <div class="flex flex-col p-4 space-y-2">
                
                {{-- Search Mobile Form --}}
                <form action="{{ route('anime.search') }}" method="GET" class="mb-2 relative">
                    <input type="text" name="s" placeholder="Cari anime..." class="w-full bg-[#141414] text-gray-300 px-4 py-2 rounded border border-[#333] focus:border-[#dd4e36] focus:outline-none">
                    <button type="submit" class="absolute right-3 top-2.5 text-gray-500">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <a href="{{ route('home') }}" class="block px-4 py-3 bg-[#2a2a2a] text-white font-bold rounded border-l-4 border-[#dd4e36]">Home</a>
                <a href="{{ route('anime.donghua') }}" class="block px-4 py-3 text-gray-300 hover:text-white hover:bg-[#2a2a2a] font-semibold rounded transition">Donghua</a>
                <a href="{{ route('anime.ongoing') }}" class="block px-4 py-3 text-gray-300 hover:text-white hover:bg-[#2a2a2a] font-semibold rounded transition">Ongoing</a>
                <a href="{{ route('anime.completed') }}" class="block px-4 py-3 text-gray-300 hover:text-white hover:bg-[#2a2a2a] font-semibold rounded transition">Completed</a>
            </div>
        </div>
    </nav>

    {{-- 2. HERO SLIDER (SWIPER) --}}
    <div class="container mx-auto px-4 py-6">
        <div class="swiper mySwiper w-full h-[200px] md:h-[380px] rounded-lg overflow-hidden relative group shadow-2xl">
            <div class="swiper-wrapper">
                {{-- Slide Item Loop --}}
                @foreach($series->take(5) as $slide)
                <div class="swiper-slide relative">
                    {{-- Background Image --}}
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 hover:scale-105" 
                         style="background-image: url('{{ $slide->image_url }}');">
                    </div>
                    {{-- Gradient Overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-[#141414] via-[#141414]/40 to-transparent"></div>
                    
                    {{-- Content --}}
                    <div class="absolute bottom-0 left-0 p-6 md:p-12 w-full md:w-2/3">
                        <span class="bg-[#dd4e36] text-white text-[10px] md:text-xs font-bold px-2 py-1 rounded mb-3 inline-block uppercase tracking-wider shadow-lg">
                            Trending Now
                        </span>
                        <h2 class="text-2xl md:text-5xl font-extrabold text-white mb-3 drop-shadow-lg leading-tight line-clamp-1">
                            {{ $slide->title }}
                        </h2>
                        <div class="flex items-center gap-4 text-xs md:text-sm text-gray-300 mb-4 font-medium">
                            <span class="flex items-center gap-1"><i class="fas fa-clock text-[#dd4e36]"></i> {{ $slide->updated_at->diffForHumans() }}</span>
                            <span class="flex items-center gap-1"><i class="fas fa-layer-group text-[#dd4e36]"></i> {{ $slide->type }}</span>
                        </div>
                        <a href="{{ route('anime.watch', [$slide->id, $slide->latestEpisode->episode_number ?? 1]) }}" 
                           class="bg-[#dd4e36] hover:bg-red-700 text-white px-8 py-3 rounded-full font-bold text-sm transition shadow-lg inline-flex items-center gap-2 group">
                            <i class="fas fa-play group-hover:scale-110 transition"></i> Watch Now
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>

    {{-- 3. MAIN CONTENT GRID --}}
    <div class="container mx-auto px-4 pb-12">
        <div class="flex flex-col lg:flex-row gap-8">
            
            {{-- KOLOM KIRI (KONTEN UTAMA) --}}
            <div class="w-full lg:w-3/4">
                
                {{-- Section Header --}}
                <div class="flex items-center justify-between mb-6 border-b border-[#333] pb-3">
                    <h3 class="text-xl font-bold text-white border-l-4 border-[#dd4e36] pl-3 uppercase tracking-wide">
                        Latest Release
                    </h3>
                    <a href="{{ route('anime.ongoing') }}" class="text-xs font-bold text-gray-400 hover:text-[#dd4e36] bg-[#1f1f1f] px-4 py-1.5 rounded-full transition border border-[#333] hover:border-[#dd4e36]">
                        VIEW ALL <i class="fas fa-angle-right ml-1"></i>
                    </a>
                </div>

                {{-- Anime Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach($series as $item)
                    <div class="group relative bg-[#1f1f1f] rounded-lg overflow-hidden hover:-translate-y-1 transition duration-300 shadow-lg border border-[#2a2a2a] hover:border-[#dd4e36]">
                        {{-- Image Wrapper --}}
                        <a href="{{ route('anime.watch', [$item->id, $item->latestEpisode->episode_number ?? 1]) }}" class="block relative aspect-[2/3] overflow-hidden">
                            {{-- Type Label (Donghua/Anime) --}}
                            <span class="absolute top-2 right-2 bg-blue-600/90 backdrop-blur-sm text-[10px] font-bold px-2 py-0.5 rounded text-white z-10 shadow-md">
                                {{ $item->type }}
                            </span>

                            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            
                            {{-- Play Icon Overlay --}}
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 bg-black/40 backdrop-blur-[2px]">
                                <div class="w-12 h-12 rounded-full bg-[#dd4e36] flex items-center justify-center text-white shadow-xl transform scale-0 group-hover:scale-100 transition duration-300">
                                    <i class="fas fa-play ml-1"></i>
                                </div>
                            </div>

                            {{-- Episode Badge (Bottom) --}}
                            <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/90 to-transparent p-3 pt-8 flex justify-between items-end">
                                <span class="text-xs font-bold text-white">Ep {{ $item->latestEpisode->episode_number ?? '?' }}</span>
                                <span class="bg-[#dd4e36] text-[9px] px-1.5 py-0.5 rounded text-white font-bold shadow-sm">SUB</span>
                            </div>
                        </a>

                        {{-- Title --}}
                        <div class="p-3">
                            <h3 class="text-sm font-bold text-gray-200 line-clamp-2 leading-snug group-hover:text-[#dd4e36] transition">
                                <a href="{{ route('anime.watch', [$item->id, $item->latestEpisode->episode_number ?? 1]) }}">
                                    {{ $item->title }}
                                </a>
                            </h3>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- KOLOM KANAN (SIDEBAR) --}}
            <div class="w-full lg:w-1/4 space-y-8">
                
                {{-- Sidebar: Ongoing --}}
                <div class="bg-[#1f1f1f] rounded-lg p-5 border border-[#333] shadow-lg">
                    <h3 class="text-lg font-bold text-white mb-5 border-b border-[#333] pb-3 flex items-center gap-2">
                        <i class="fas fa-fire text-[#dd4e36]"></i> Ongoing Series
                    </h3>
                    <div class="space-y-4">
                        @foreach($series->take(5) as $sideItem)
                        <a href="{{ route('anime.watch', [$sideItem->id, $sideItem->latestEpisode->episode_number ?? 1]) }}" class="flex gap-4 group items-center">
                            <div class="w-16 h-24 flex-shrink-0 overflow-hidden rounded-md relative shadow-md">
                                <img src="{{ $sideItem->image_url }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                <div class="absolute top-0 left-0 w-5 h-5 bg-[#dd4e36] text-white text-[10px] flex items-center justify-center font-bold rounded-br-md shadow-sm">
                                    {{ $loop->iteration }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-gray-300 group-hover:text-[#dd4e36] line-clamp-2 transition leading-tight">{{ $sideItem->title }}</h4>
                                <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                                    <span class="bg-[#2a2a2a] px-2 py-0.5 rounded text-gray-400 border border-[#333]">Ep {{ $sideItem->latestEpisode->episode_number ?? '?' }}</span>
                                    <span><i class="fas fa-eye mr-1"></i> {{ rand(1000, 9999) }}</span>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Sidebar: Genres --}}
                <div class="bg-[#1f1f1f] rounded-lg p-5 border border-[#333] shadow-lg">
                    <h3 class="text-lg font-bold text-white mb-5 border-b border-[#333] pb-3 flex items-center gap-2">
                        <i class="fas fa-tags text-[#dd4e36]"></i> Genres
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @php $genres = ['Action', 'Adventure', 'Cultivation', 'Fantasy', 'Martial Arts', 'Romance', 'Harem', 'Isekai', 'Magic', 'Comedy']; @endphp
                        @foreach($genres as $genre)
                        <a href="#" class="text-[11px] font-semibold bg-[#2a2a2a] hover:bg-[#dd4e36] text-gray-400 hover:text-white px-3 py-1.5 rounded transition border border-[#333] hover:border-[#dd4e36]">
                            {{ $genre }}
                        </a>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- 4. FOOTER --}}
    <footer class="bg-[#1a1a1a] border-t border-[#333] pt-12 pb-8 mt-10">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-extrabold mb-6 tracking-wide text-white">ANIME<span class="text-[#dd4e36]">STREAM</span></h2>
            
            {{-- A-Z List --}}
            <div class="flex flex-wrap justify-center gap-2 mb-8 text-xs font-bold text-gray-500 max-w-2xl mx-auto">
                <a href="#" class="px-2 py-1 hover:text-[#dd4e36] hover:bg-[#2a2a2a] rounded transition">#</a>
                @foreach(range('A', 'Z') as $char)
                    <a href="#" class="px-2 py-1 hover:text-[#dd4e36] hover:bg-[#2a2a2a] rounded transition">{{ $char }}</a>
                @endforeach
            </div>

            <div class="text-gray-500 text-sm leading-relaxed">
                <p>Copyright © {{ date('Y') }} AnimeStream. All Rights Reserved.</p>
                <p class="text-xs mt-2 text-gray-600">Disclaimer: This site does not store any files on its server. All contents are provided by non-affiliated third parties.</p>
            </div>
        </div>
    </footer>

    {{-- SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        // Init Swiper
        var swiper = new Swiper(".mySwiper", {
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
        });

        // Mobile Menu Logic
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>