<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-6">

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg sm:text-2xl font-bold text-gray-800 dark:text-white border-l-4 border-red-600 pl-3">
                    Update Terbaru
                </h2>
                <a href="#" class="text-xs sm:text-sm text-gray-400 hover:text-white transition">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-6">
                
                @foreach($series as $data) 
                <div class="bg-gray-800 rounded-lg overflow-hidden shadow-lg relative group hover:-translate-y-1 transition duration-300">
                    
                    <a href="{{ route('anime.watch', ['id' => $data->id, 'ep' => 1]) }}" class="block relative aspect-[2/3] overflow-hidden">
                        
                        <div class="absolute top-0 right-0 z-10">
                            <span class="bg-red-600 text-white text-[10px] sm:text-xs font-bold px-2 py-1 rounded-bl-lg shadow-md">
                                {{ $data->type ?? 'Donghua' }}
                            </span>
                        </div>

                        <img src="{{ $data->poster_image ? asset($data->poster_image) : 'https://via.placeholder.com/300x450' }}" 
                             alt="{{ $data->title }}" 
                             class="w-full h-full object-cover transform group-hover:scale-110 group-hover:opacity-80 transition duration-500 ease-in-out"
                             loading="lazy">

                        <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black via-black/70 to-transparent p-2 pt-10">
                            <span class="text-white text-xs font-bold bg-black/50 px-2 py-0.5 rounded border border-white/20">
                                Ep {{ $data->latest_episode ?? '?' }}
                            </span>
                        </div>
                    </a>

                    <div class="p-2 sm:p-3">
                        <h3 class="text-gray-100 text-xs sm:text-sm font-semibold leading-tight line-clamp-2 min-h-[2.5em]" title="{{ $data->title }}">
                            <a href="{{ route('anime.watch', ['id' => $data->id, 'ep' => 1]) }}" class="hover:text-red-500 transition-colors">
                                {{ $data->title }}
                            </a>
                        </h3>
                    </div>

                </div>
                @endforeach

            </div>
            
            <div class="mt-8 px-4 text-white">
                {{ $series->links() }}
            </div>

        </div>
        </div>
</x-app-layout>