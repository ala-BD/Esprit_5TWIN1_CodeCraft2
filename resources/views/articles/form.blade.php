@extends('layouts.app-auth')

@section('title', ($isEdit ? 'Modifier l\'article' : 'Publier un article') . ' — Marketplace RETISS')

@section('styles')
<style>
    .article-form-page {
        min-height: 100vh;
        background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 40%, #f0f4ff 100%);
        padding-top: 72px;
    }

    .article-form-hero {
        background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
        position: relative;
        overflow: hidden;
        padding: 2.5rem 0 5rem;
    }
    .article-form-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(ellipse at 80% 50%, rgba(13,148,136,0.25) 0%, transparent 60%),
            radial-gradient(ellipse at 20% 80%, rgba(74,222,128,0.1) 0%, transparent 50%);
        pointer-events: none;
    }
    .hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 40px 40px;
        pointer-events: none;
    }

    .form-card-wrap {
        margin-top: -3.5rem;
        position: relative;
        z-index: 10;
    }

    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        letter-spacing: 0.01em;
    }
    .form-input {
        width: 100%;
        padding: 0.85rem 1rem 0.85rem 2.75rem;
        border-radius: 0.875rem;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        font-size: 0.9rem;
        color: #0f172a;
        transition: all 0.2s;
        outline: none;
        font-family: 'Inter', sans-serif;
    }
    .form-input:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13,148,136,0.12);
    }
    .input-wrap { position: relative; }
    .input-icon {
        position: absolute;
        left: 1rem; top: 50%;
        transform: translateY(-50%);
        color: #94a3b8; font-size: 0.85rem;
        pointer-events: none; transition: color 0.2s;
    }
    .input-wrap:focus-within .input-icon { color: #0d9488; }

    .image-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 1.25rem;
        padding: 1.5rem;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.2s;
    }
    .image-dropzone:hover {
        border-color: #0d9488;
        background: #f0fdf9;
    }
</style>
@endsection

@section('content')
<div class="article-form-page">

    {{-- ===== HERO ===== --}}
    <div class="article-form-hero">
        <div class="hero-grid"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <a href="{{ route('articles.index') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    Marketplace
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-medium">
                    {{ $isEdit ? 'Modifier l\'article' : 'Publier un article' }}
                </span>
            </div>

            <h1 class="font-display text-3xl font-extrabold text-white mb-2">
                {{ $isEdit ? 'Modifier votre article' : 'Publier un article textile' }}
            </h1>
            <p class="text-white/70 text-sm">
                {{ $isEdit ? 'Mettez à jour les informations, le prix, le stock ou les photos de votre article.' : 'Complétez les détails ci-dessous et ajoutez autant de photos que vous le souhaitez.' }}
            </p>

        </div>
    </div>

    {{-- ===== MAIN CONTENT ===== --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 form-card-wrap pb-16">

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-10">

            <form method="POST"
                  action="{{ $isEdit ? route('articles.update', $article) : route('articles.store') }}"
                  enctype="multipart/form-data"
                  class="space-y-6">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                {{-- Titre --}}
                <div>
                    <label for="titre" class="form-label">
                        Titre de l'article <span class="text-red-500">*</span>
                    </label>
                    <div class="input-wrap">
                        <i class="fas fa-heading input-icon"></i>
                        <input id="titre" name="titre" type="text"
                               class="form-input {{ $errors->has('titre') ? 'border-red-400 bg-red-50' : '' }}"
                               value="{{ old('titre', $article->titre) }}"
                               placeholder="ex. Veste en jean upcyclée brodée main, Pull en laine mérinos…"
                               required maxlength="255">
                    </div>
                    @error('titre')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Catégorie (+ Statut si modification) --}}
                <div class="grid grid-cols-1 {{ $isEdit ? 'sm:grid-cols-2' : '' }} gap-5">
                    <div>
                        <label for="categorie" class="form-label">
                            Catégorie <span class="text-red-500">*</span>
                        </label>
                        <div class="input-wrap">
                            <i class="fas fa-tags input-icon"></i>
                            <select id="categorie" name="categorie"
                                    class="form-input bg-white {{ $errors->has('categorie') ? 'border-red-400 bg-red-50' : '' }}"
                                    required>
                                <option value="" disabled {{ old('categorie', $article->categorie) ? '' : 'selected' }}>
                                    Sélectionnez une catégorie
                                </option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('categorie', $article->categorie) === $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('categorie')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    @if($isEdit)
                        <div>
                            <label for="statut" class="form-label">
                                Statut de l'article <span class="text-red-500">*</span>
                            </label>
                            <div class="input-wrap">
                                <i class="fas fa-info-circle input-icon"></i>
                                <select id="statut" name="statut"
                                        class="form-input bg-white {{ $errors->has('statut') ? 'border-red-400 bg-red-50' : '' }}">
                                    @foreach($statuts as $st)
                                        <option value="{{ $st }}" {{ old('statut', $article->statut) === $st ? 'selected' : '' }}>
                                            {{ $st }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('statut')
                                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endif
                </div>

                {{-- Prix & Stock --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    @if(auth()->user()->role === \App\Models\User::ROLE_DONATEUR)
                        {{-- En tant que Donateur, l'article est un don gratuit : prix = 0 --}}
                        <input type="hidden" name="prix" value="0.00">
                        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-lg flex-shrink-0">
                                <i class="fas fa-hand-holding-heart"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Don Solidaire (0.00 DT)</p>
                                <p class="text-xs text-emerald-700">En tant que donateur, votre article est offert gratuitement (prix 0 DT).</p>
                            </div>
                        </div>
                    @else
                        {{-- Prix de vente (Atelier & Admin) --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="prix" class="form-label mb-0">
                                    Prix de vente (DT) <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[10px] text-teal-600 font-semibold">> 0 DT</span>
                            </div>
                            <div class="input-wrap">
                                <i class="fas fa-coins input-icon"></i>
                                <input id="prix" name="prix" type="number" step="0.01" min="0.01"
                                       class="form-input {{ $errors->has('prix') ? 'border-red-400 bg-red-50' : '' }}"
                                       value="{{ old('prix', $article->prix) }}"
                                       placeholder="ex. 45.00"
                                       required>
                            </div>
                            @error('prix')
                                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endif

                    {{-- Stock (entier requis) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="stock" class="form-label mb-0">
                                Quantité en stock <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[10px] text-teal-600 font-semibold">Entier &ge; 0</span>
                        </div>
                        <div class="input-wrap">
                            <i class="fas fa-boxes input-icon"></i>
                            <input id="stock" name="stock" type="number" step="1" min="0"
                                   class="form-input {{ $errors->has('stock') ? 'border-red-400 bg-red-50' : '' }}"
                                   value="{{ old('stock', $article->stock ?? 1) }}"
                                   placeholder="ex. 1"
                                   required>
                        </div>
                        @error('stock')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Photos de l'article (Upload multiple sans limite) --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="form-label mb-0">
                            Photos de l'article <span class="text-slate-400 font-normal">(illimitées)</span>
                        </label>
                        <span class="text-[11px] text-teal-600 font-semibold flex items-center gap-1">
                            <i class="fas fa-sparkles"></i> Formats JPG, PNG, WEBP
                        </span>
                    </div>

                    {{-- Ultra-Modern Dropzone --}}
                    <div class="relative group cursor-pointer" id="dropzone" onclick="triggerFileInput()">
                        <div class="border-2 border-dashed border-slate-300 group-hover:border-teal-500 rounded-3xl p-6 sm:p-8 text-center bg-gradient-to-b from-slate-50/70 to-white group-hover:from-teal-50/40 group-hover:to-white transition-all duration-300 shadow-sm hover:shadow-md">
                            <div class="w-14 h-14 rounded-2xl bg-teal-500/10 text-teal-600 group-hover:bg-teal-500 group-hover:text-white flex items-center justify-center mx-auto mb-3 text-2xl transition-all duration-300 transform group-hover:scale-110 shadow-sm">
                                <i class="fas fa-cloud-arrow-up"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-800 mb-1 group-hover:text-teal-700 transition-colors">
                                Glissez-déposez vos photos ou <span class="text-teal-600 underline decoration-teal-300 underline-offset-4 font-extrabold">parcourez vos fichiers</span>
                            </p>
                            <p class="text-xs text-slate-400">
                                Ajoutez autant de visuels que vous voulez pour mettre en valeur votre création
                            </p>
                        </div>
                        <input id="images" name="images[]" type="file" multiple accept="image/*" class="hidden" onchange="handleNewFiles(this.files)">
                        <input id="replace-single-input" type="file" accept="image/*" class="hidden" onchange="handleFileReplacement(this)">
                    </div>

                    {{-- Dynamic Preview of newly chosen files --}}
                    <div id="new-images-container" class="mt-5 hidden">
                        <div class="flex items-center justify-between mb-3 px-1">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                Nouvelles photos (<span id="new-images-count">0</span>)
                            </span>
                            <button type="button" onclick="clearAllNewImages()" class="text-xs text-rose-500 hover:text-rose-700 font-semibold flex items-center gap-1 transition-colors">
                                <i class="fas fa-trash-can text-[11px]"></i> Tout retirer
                            </button>
                        </div>
                        <div id="new-images-preview" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3.5"></div>
                    </div>

                    @error('images')
                        <p class="mt-2 text-xs text-rose-500 flex items-center gap-1 font-medium">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                    @error('images.*')
                        <p class="mt-2 text-xs text-rose-500 flex items-center gap-1 font-medium">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Existing images (on Edit) --}}
                @if($isEdit && !empty($article->images))
                    <div class="p-5 bg-gradient-to-b from-slate-50 to-white border border-slate-200/80 rounded-3xl shadow-sm">
                        <div class="flex items-center justify-between mb-3.5">
                            <div>
                                <p class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                    <i class="fas fa-images text-teal-600"></i> Photos actuelles en ligne
                                </p>
                                <p class="text-[11px] text-slate-400">
                                    Survolez une photo et cliquez sur l'icône corbeille pour la supprimer
                                </p>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full">
                                {{ count($article->images) }} photo(s)
                            </span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5">
                            @foreach($article->images as $idx => $img)
                                @php
                                    $imgUrl = str_starts_with($img, 'http') ? $img : \Illuminate\Support\Facades\Storage::url($img);
                                @endphp
                                <div class="relative group rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 shadow-sm transition-all duration-300 aspect-square" id="existing-img-card-{{ $idx }}">
                                    <img src="{{ $imgUrl }}"
                                         alt="Photo {{ $idx + 1 }}"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                    
                                    {{-- Badge index / couverture --}}
                                    <div class="absolute top-2 left-2 z-10">
                                        @if($idx === 0)
                                            <span class="bg-gradient-to-r from-teal-600 to-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-md">
                                                Couverture
                                            </span>
                                        @else
                                            <span class="bg-black/60 backdrop-blur-md text-white text-[10px] font-medium px-2 py-0.5 rounded-full">
                                                #{{ $idx + 1 }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Hover Overlay with Minimalist Glassmorphic Delete Icon Button --}}
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center z-20 backdrop-blur-[2px]">
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="remove_images[]" value="{{ $img }}"
                                                   class="sr-only existing-remove-checkbox"
                                                   onchange="toggleExistingDeleteState({{ $idx }}, this.checked)">
                                            <div class="w-11 h-11 rounded-full bg-white/90 hover:bg-rose-600 text-slate-800 hover:text-white shadow-xl flex items-center justify-center transition-all duration-200 transform hover:scale-110 active:scale-90 group/btn relative"
                                                 title="Supprimer cette photo">
                                                <i class="fas fa-trash-can text-sm transition-transform group-hover/btn:rotate-12"></i>
                                                {{-- Tooltip --}}
                                                <span class="absolute -bottom-8 whitespace-nowrap bg-black/80 backdrop-blur-md text-white text-[10px] font-medium px-2 py-1 rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity pointer-events-none shadow-lg">
                                                    Supprimer
                                                </span>
                                            </div>
                                        </label>
                                    </div>

                                    {{-- Delete Indicator Badge when marked --}}
                                    <div id="existing-badge-{{ $idx }}" class="absolute inset-0 bg-rose-900/60 backdrop-blur-sm flex flex-col items-center justify-center text-white z-30 hidden transition-all">
                                        <div class="w-10 h-10 rounded-full bg-rose-600 text-white flex items-center justify-center shadow-lg mb-1 animate-bounce">
                                            <i class="fas fa-trash-can text-sm"></i>
                                        </div>
                                        <span class="text-[11px] font-bold">À supprimer</span>
                                        <label class="mt-2 text-[10px] underline cursor-pointer hover:text-rose-200 font-semibold">
                                            <input type="checkbox" class="sr-only" onchange="cancelExistingDelete({{ $idx }})">
                                            Annuler
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Description --}}
                <div>
                    <label for="description" class="form-label">
                        Description de l'article <span class="text-slate-400 font-normal">(optionnel)</span>
                    </label>
                    <textarea id="description" name="description" rows="4"
                              class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition-all {{ $errors->has('description') ? 'border-red-400 bg-red-50' : '' }}"
                              placeholder="Détaillez la coupe, l'histoire de la pièce, la composition textile, la taille exacte, les conseils d'entretien…">{{ old('description', $article->description) }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Association facultative à un don textile --}}
                @if(isset($userDons) && $userDons->count() > 0)
                    <div class="p-4 bg-teal-50/60 border border-teal-100 rounded-2xl">
                        <label for="don_vetement_id" class="form-label text-teal-900 flex items-center gap-2">
                            <i class="fas fa-hand-holding-heart text-teal-600"></i>
                            <span>Associer à un vêtement donné (Optionnel)</span>
                        </label>
                        <p class="text-xs text-slate-500 mb-2.5">
                            Si cet article provient d'un vêtement que vous avez donné pour valorisation ou upcycling, associez-le pour afficher sa traçabilité.
                        </p>
                        <select id="don_vetement_id" name="don_vetement_id"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-700 outline-none focus:border-teal-500">
                            <option value="">-- Aucun don associé --</option>
                            @foreach($userDons as $don)
                                <option value="{{ $don->id }}" {{ (string) old('don_vetement_id', $article->don_vetement_id) === (string) $don->id ? 'selected' : '' }}>
                                    #{{ $don->id }} — {{ $don->type }} ({{ $don->matiere }}, Taille {{ $don->taille }}) — Déposé le {{ $don->date_depot?->format('d/m/Y') ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        @error('don_vetement_id')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="pt-4 flex flex-col sm:flex-row items-center gap-3 border-t border-slate-100">
                    <button type="submit"
                            class="w-full sm:w-auto px-7 py-3 rounded-2xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm shadow-md shadow-teal-600/20 transition-all flex items-center justify-center gap-2">
                        <i class="fas {{ $isEdit ? 'fa-check' : 'fa-paper-plane' }}"></i>
                        <span>{{ $isEdit ? 'Mettre à jour l\'article' : 'Publier sur la marketplace' }}</span>
                    </button>

                    <a href="{{ $isEdit ? route('articles.show', $article) : route('articles.index') }}"
                       class="w-full sm:w-auto text-center px-6 py-3 rounded-2xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition-colors"
                       style="text-decoration:none">
                        Annuler
                    </a>
                </div>

            </form>

        </div>

    </div>
</div>

<script>
// Array maintaining the current list of File objects to be uploaded
let uploadedFiles = [];
let fileIndexToReplace = null;

function triggerFileInput() {
    document.getElementById('images').click();
}

function handleNewFiles(fileList) {
    if (!fileList || fileList.length === 0) return;
    
    for (let i = 0; i < fileList.length; i++) {
        uploadedFiles.push(fileList[i]);
    }
    
    syncFileInputAndRender();
}

function removeNewFile(index, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    if (index >= 0 && index < uploadedFiles.length) {
        uploadedFiles.splice(index, 1);
        syncFileInputAndRender();
    }
}

function promptReplaceNewFile(index, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    fileIndexToReplace = index;
    const replaceInput = document.getElementById('replace-single-input');
    replaceInput.value = '';
    replaceInput.click();
}

function handleFileReplacement(input) {
    if (input.files && input.files[0] && fileIndexToReplace !== null && fileIndexToReplace < uploadedFiles.length) {
        uploadedFiles[fileIndexToReplace] = input.files[0];
        fileIndexToReplace = null;
        syncFileInputAndRender();
    }
}

function clearAllNewImages() {
    uploadedFiles = [];
    syncFileInputAndRender();
}

function syncFileInputAndRender() {
    const input = document.getElementById('images');
    const container = document.getElementById('new-images-container');
    const preview = document.getElementById('new-images-preview');
    const countBadge = document.getElementById('new-images-count');
    
    // Sync with DataTransfer
    const dt = new DataTransfer();
    uploadedFiles.forEach(file => dt.items.add(file));
    input.files = dt.files;
    
    // Clear and re-render preview
    preview.innerHTML = '';
    countBadge.textContent = uploadedFiles.length;
    
    if (uploadedFiles.length === 0) {
        container.classList.add('hidden');
        return;
    }
    
    container.classList.remove('hidden');
    
    uploadedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const card = document.createElement('div');
            card.className = 'relative group rounded-2xl overflow-hidden border border-slate-200/80 bg-slate-100 shadow-sm transition-all duration-300 aspect-square';
            
            const isCover = index === 0;
            const badgeHtml = isCover
                ? `<span class="bg-gradient-to-r from-teal-600 to-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-md">Couverture</span>`
                : `<span class="bg-black/60 backdrop-blur-md text-white text-[10px] font-medium px-2 py-0.5 rounded-full">#${index + 1}</span>`;

            card.innerHTML = `
                <img src="${e.target.result}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="Aperçu ${index + 1}">
                
                <!-- Badge Tag -->
                <div class="absolute top-2 left-2 z-10">
                    ${badgeHtml}
                </div>

                <!-- Sleek Minimalist Glassmorphic Actions on Hover -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center gap-3.5 z-20 backdrop-blur-[2px]">
                    
                    <!-- Icon Button: Remplacer / Changer -->
                    <button type="button" onclick="promptReplaceNewFile(${index}, event)"
                            class="w-10 h-10 rounded-full bg-white/90 hover:bg-teal-600 text-slate-800 hover:text-white shadow-xl flex items-center justify-center transition-all duration-200 transform hover:scale-115 active:scale-90 group/btn relative"
                            title="Remplacer cette photo">
                        <i class="fas fa-arrows-rotate text-xs transition-transform group-hover/btn:rotate-180 duration-300"></i>
                        <span class="absolute -bottom-8 whitespace-nowrap bg-black/80 backdrop-blur-md text-white text-[10px] font-medium px-2 py-1 rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity pointer-events-none shadow-lg">
                            Remplacer
                        </span>
                    </button>
                    
                    <!-- Icon Button: Supprimer -->
                    <button type="button" onclick="removeNewFile(${index}, event)"
                            class="w-10 h-10 rounded-full bg-white/90 hover:bg-rose-600 text-slate-800 hover:text-white shadow-xl flex items-center justify-center transition-all duration-200 transform hover:scale-115 active:scale-90 group/btn relative"
                            title="Supprimer cette photo">
                        <i class="fas fa-trash-can text-xs transition-transform group-hover/btn:scale-110"></i>
                        <span class="absolute -bottom-8 whitespace-nowrap bg-black/80 backdrop-blur-md text-white text-[10px] font-medium px-2 py-1 rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity pointer-events-none shadow-lg">
                            Supprimer
                        </span>
                    </button>
                </div>
            `;
            preview.appendChild(card);
        };
        reader.readAsDataURL(file);
    });
}

function toggleExistingDeleteState(idx, isChecked) {
    const card = document.getElementById('existing-img-card-' + idx);
    const badge = document.getElementById('existing-badge-' + idx);
    
    if (isChecked) {
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

function cancelExistingDelete(idx) {
    const card = document.getElementById('existing-img-card-' + idx);
    const checkbox = card.querySelector('.existing-remove-checkbox');
    const badge = document.getElementById('existing-badge-' + idx);
    
    if (checkbox) {
        checkbox.checked = false;
    }
    if (badge) {
        badge.classList.add('hidden');
    }
}

// Drag and drop handling on dropzone
const dropzone = document.getElementById('dropzone');
['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.querySelector('div').classList.add('border-teal-500', 'bg-teal-50/60');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.querySelector('div').classList.remove('border-teal-500', 'bg-teal-50/60');
    }, false);
});

dropzone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length > 0) {
        handleNewFiles(dt.files);
    }
}, false);
</script>
@endsection
