@extends('layouts.app')

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

                {{-- Catégorie + Statut --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
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

                    <div>
                        <label for="statut" class="form-label">
                            Statut de l'article
                        </label>
                        <div class="input-wrap">
                            <i class="fas fa-info-circle input-icon"></i>
                            <select id="statut" name="statut"
                                    class="form-input bg-white {{ $errors->has('statut') ? 'border-red-400 bg-red-50' : '' }}">
                                @foreach($statuts as $st)
                                    <option value="{{ $st }}" {{ old('statut', $article->statut ?? 'DISPONIBLE') === $st ? 'selected' : '' }}>
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
                </div>

                {{-- Prix & Stock --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    {{-- Prix de vente (positif requis) --}}
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
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="form-label mb-0">
                            Photos de l'article <span class="text-slate-400 font-normal">(autant que souhaité)</span>
                        </label>
                        <span class="text-[11px] text-teal-600 font-semibold">
                            <i class="fas fa-images mr-1"></i> Formats JPG, PNG, WEBP
                        </span>
                    </div>

                    <div class="image-dropzone" onclick="document.getElementById('images').click()">
                        <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-600 flex items-center justify-center mx-auto mb-3 text-xl">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-800 mb-1">
                            Cliquez pour choisir des images ou déposez-les ici
                        </p>
                        <p class="text-xs text-slate-400">
                            Vous pouvez sélectionner plusieurs photos à la fois
                        </p>
                        <input id="images" name="images[]" type="file" multiple accept="image/*" class="hidden" onchange="previewSelectedImages(this)">
                    </div>

                    {{-- Dynamic Preview of newly chosen files --}}
                    <div id="new-images-preview" class="grid grid-cols-3 sm:grid-cols-6 gap-3 mt-3 hidden"></div>

                    @error('images')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                    @error('images.*')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Existing images (on Edit) --}}
                @if($isEdit && !empty($article->images))
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                        <p class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
                            Photos actuelles (Cochez pour supprimer)
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($article->images as $idx => $img)
                                <div class="relative rounded-xl overflow-hidden border border-slate-200 bg-white p-1 group">
                                    <img src="{{ str_starts_with($img, 'http') ? $img : \Illuminate\Support\Facades\Storage::url($img) }}"
                                         alt="Photo article"
                                         class="w-full h-24 object-cover rounded-lg">
                                    <label class="flex items-center gap-1.5 p-1 text-[11px] text-red-600 cursor-pointer mt-1">
                                        <input type="checkbox" name="remove_images[]" value="{{ $img }}" class="rounded text-red-600 focus:ring-red-500">
                                        <span>Supprimer</span>
                                    </label>
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
function previewSelectedImages(input) {
    const previewContainer = document.getElementById('new-images-preview');
    previewContainer.innerHTML = '';

    if (input.files && input.files.length > 0) {
        previewContainer.classList.remove('hidden');
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative rounded-xl overflow-hidden border border-teal-200 bg-slate-50 h-20 shadow-sm';
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    } else {
        previewContainer.classList.add('hidden');
    }
}
</script>
@endsection
