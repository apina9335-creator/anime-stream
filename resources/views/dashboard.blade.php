<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-6">

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg sm:text-2xl font-bold text-white border-l-4 border-red-600 pl-3">
            Update Terbaru
        </h2>
        <a href="#" class="text-xs sm:text-sm text-gray-400 hover:text-white transition">
            Lihat Semua &rarr;
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-6">
        
        @foreach($series as $data) <div class="bg-gray-800 rounded-lg overflow-hidden shadow-lg relative group hover:-translate-y-1 transition duration-300">
                
                <a href="{{ route('anime.show', $data->id) }}" class="block relative aspect-[2/3] overflow-hidden">
                    
                    <div class="absolute top-0 right-0 z-10">
                        <span class="bg-red-600 text-white text-[10px] sm:text-xs font-bold px-2 py-1 rounded-bl-lg shadow-md">
                            Donghua
                        </span>
                    </div>

                    <img src="{{ $data->poster_image ?? 'https://via.placeholder.com/300x450' }}" 
                         alt="{{ $data->title }}" 
                         class="w-full h-full object-cover transform group-hover:scale-110 group-hover:opacity-80 transition duration-500 ease-in-out"
                         loading="lazy">

                    <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black via-black/70 to-transparent p-2 pt-10">
                        <span class="text-white text-xs font-bold bg-black/50 px-2 py-0.5 rounded border border-white/20">
                            Ep {{ $data->latest_episode ?? '?' }}
                        </span>
                    </div>

                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300">
                        <div class="bg-red-600 rounded-full p-3 shadow-lg transform scale-0 group-hover:scale-100 transition duration-300">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path></svg>
                        </div>
                    </div>
                </a>

                <div class="p-2 sm:p-3">
                    <h3 class="text-gray-100 text-xs sm:text-sm font-semibold leading-tight line-clamp-2 min-h-[2.5em]" title="{{ $data->title }}">
                        <a href="{{ route('anime.show', $data->id) }}" class="hover:text-red-500 transition-colors">
                            {{ $data->title }}
                        </a>
                    </h3>
                    
                    <div class="flex items-center justify-between mt-2 text-[10px] text-gray-400">
                        <span class="flex items-center">
                            <svg class="w-3 h-3 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            {{ $data->rating ?? 'N/A' }}
                        </span>
                        <span>{{ $data->year ?? '2024' }}</span>
                    </div>
                </div>

            </div>
            @endforeach

    </div>
    
    <div class="mt-8 px-4">
        {{ $series->links() }}
    </div>

</div>