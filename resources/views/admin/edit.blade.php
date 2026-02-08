<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <h2 class="font-bold text-2xl text-white mb-6">✏️ Edit Anime: {{ $anime->title }}</h2>

            @if ($errors->any())
                <div class="bg-red-600 text-white p-4 rounded mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>⚠️ {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700">
                
                <form action="{{ route('admin.series.update', $anime->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2">Judul Anime</label>
                        <input type="text" name="title" value="{{ $anime->title }}" 
                               class="w-full rounded bg-gray-900 text-white border-gray-600 focus:border-blue-500 p-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-300 mb-2">Link Sumber (Anichin)</label>
                        <input type="url" name="source_url" value="{{ $anime->source_url }}" 
                               class="w-full rounded bg-gray-900 text-white border-gray-600 focus:border-blue-500 p-2">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2">Ganti Poster (Upload)</label>
                        
                        <div class="mb-2">
                            <img src="{{ asset($anime->poster_image) }}" alt="Poster Lama" class="h-32 w-auto rounded border border-gray-600">
                            <p class="text-xs text-gray-500">Poster saat ini</p>
                        </div>

                        <input type="file" name="poster_image" accept="image/png, image/jpeg, image/jpg, image/webp"
                               class="w-full text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                        
                        <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, JPEG, WEBP.</p>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('admin.dashboard') }}" class="bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded">Batal</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>