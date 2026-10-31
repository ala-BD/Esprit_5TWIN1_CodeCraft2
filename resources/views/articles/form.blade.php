@extends('layouts.app')

@section('title', ($isEdit ? 'Modifier l\'article' : 'Publier un article') . ' — Marketplace RETISS')

@section('styles')
<style>
    .article-form-page{
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
                  x-data="assistantEstimation({
                      articleId: {{ $article->id ?? 'null' }},
                      isAtelier: {{ (auth()->user()->role === \App\Models\User::ROLE_ATELIER || auth()->user()->isAdmin()) ? 'true' : 'false' }},
                      initialPrix: '{{ old('prix', $article->prix ?? '') }}',
                      initialMatiere: '{{ old('matiere', $article->matiere ?? '') }}',
                      initialCategorie: '{{ old('categorie', $article->categorie ?? '') }}',
                      initialEtat: '{{ old('etat', $article->etat ?? '') }}',
                      initialTaille: '{{ old('taille', $article->taille ?? '') }}',
                      initialCouleur: '{{ old('couleur', $article->couleur ?? '') }}',
                      initialPoids: '{{ old('poids_kg', $article->poids_kg ?? 0.5) }}',
                      initialAchat: '{{ old('cout_achat', $article->cout_achat ?? 0.0) }}',
                      initialHeures: '{{ old('heures_travail', $article->heures_travail ?? 1.0) }}',
                      initialMateriaux: '{{ old('cout_materiaux', $article->cout_materiaux ?? 0.0) }}',
                      csrfToken: '{{ csrf_token() }}',
                      endpointCalculer: '{{ route('atelier.estimations.calculer') }}'
                  })"
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

                {{-- Caractéristiques Textiles (Matière, Taille, État, Couleur) --}}
                <div class="p-5 bg-slate-50/70 border border-slate-200/80 rounded-2xl">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="fas fa-layer-group text-teal-600"></i> Caractéristiques textiles de la pièce
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        {{-- Matière --}}
                        <div>
                            <label for="matiere" class="form-label text-xs">Matière</label>
                            <div class="input-wrap">
                                <i class="fas fa-certificate input-icon text-xs"></i>
                                <select id="matiere" name="matiere" x-model="form.matiere"
                                        class="form-input text-xs bg-white {{ $errors->has('matiere') ? 'border-red-400 bg-red-50' : '' }}">
                                    <option value="">Sélectionnez</option>
                                    @foreach($matieres as $mat)
                                        <option value="{{ $mat }}" {{ old('matiere', $article->matiere) === $mat ? 'selected' : '' }}>
                                            {{ $mat }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('matiere')
                                <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Taille --}}
                        <div>
                            <label for="taille" class="form-label text-xs">Taille</label>
                            <div class="input-wrap">
                                <i class="fas fa-ruler-combined input-icon text-xs"></i>
                                <select id="taille" name="taille" x-model="form.taille"
                                        class="form-input text-xs bg-white {{ $errors->has('taille') ? 'border-red-400 bg-red-50' : '' }}">
                                    <option value="">Sélectionnez</option>
                                    @foreach($tailles as $t)
                                        <option value="{{ $t }}" {{ old('taille', $article->taille) === $t ? 'selected' : '' }}>
                                            {{ $t }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('taille')
                                <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- État --}}
                        <div>
                            <label for="etat" class="form-label text-xs">État</label>
                            <div class="input-wrap">
                                <i class="fas fa-sparkles input-icon text-xs"></i>
                                <select id="etat" name="etat" x-model="form.etat"
                                        class="form-input text-xs bg-white {{ $errors->has('etat') ? 'border-red-400 bg-red-50' : '' }}">
                                    <option value="">Sélectionnez</option>
                                    @foreach($etats as $et)
                                        <option value="{{ $et }}" {{ old('etat', $article->etat) === $et ? 'selected' : '' }}>
                                            {{ $et }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('etat')
                                <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Couleur --}}
                        <div>
                            <label for="couleur" class="form-label text-xs">Couleur</label>
                            <div class="input-wrap">
                                <i class="fas fa-palette input-icon text-xs"></i>
                                <select id="couleur" name="couleur" x-model="form.couleur"
                                        class="form-input text-xs bg-white {{ $errors->has('couleur') ? 'border-red-400 bg-red-50' : '' }}">
                                    <option value="">Sélectionnez</option>
                                    @foreach($couleurs as $c)
                                        <option value="{{ $c }}" {{ old('couleur', $article->couleur) === $c ? 'selected' : '' }}>
                                            {{ $c }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('couleur')
                                <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Données spécifiques Atelier : Charges d'Upcycling & Poids --}}
                @if(auth()->user()->role === \App\Models\User::ROLE_ATELIER || auth()->user()->isAdmin())
                <div class="p-5 bg-gradient-to-br from-amber-50/60 to-orange-50/40 border border-amber-200/80 rounded-2xl">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="fas fa-scissors text-amber-600"></i> Charges & Données Atelier (Upcycling / Réparation)
                        </h3>
                        <span class="text-[11px] font-semibold text-amber-700 bg-amber-100/80 px-2.5 py-0.5 rounded-full">
                            Tarif atelier : {{ auth()->user()->tarif_horaire_effectif }} DT/h
                        </span>
                    </div>
                    <p class="text-xs text-amber-800/80 mb-4">
                        Ces données permettent à l'assistant d'estimer vos coûts de revient réels, de simuler la filière recyclage et de calculer votre rentabilité nette.
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label for="poids_kg" class="form-label text-xs">Poids (kg)</label>
                            <div class="input-wrap">
                                <i class="fas fa-weight-hanging input-icon text-xs"></i>
                                <input id="poids_kg" name="poids_kg" type="number" step="0.01" min="0.01" max="50"
                                       x-model="form.poids_kg"
                                       value="{{ old('poids_kg', $article->poids_kg ?? 0.5) }}"
                                       class="form-input text-xs bg-white"
                                       placeholder="ex. 0.45">
                            </div>
                        </div>

                        <div>
                            <label for="cout_achat" class="form-label text-xs">Coût d'achat (DT)</label>
                            <div class="input-wrap">
                                <i class="fas fa-receipt input-icon text-xs"></i>
                                <input id="cout_achat" name="cout_achat" type="number" step="0.01" min="0"
                                       x-model="form.cout_achat"
                                       value="{{ old('cout_achat', $article->cout_achat ?? 0.0) }}"
                                       class="form-input text-xs bg-white"
                                       placeholder="0.00 si don">
                            </div>
                            <span class="text-[10px] text-gray-500">0 DT si don</span>
                        </div>

                        <div>
                            <label for="heures_travail" class="form-label text-xs">Heures de travail (h)</label>
                            <div class="input-wrap">
                                <i class="fas fa-clock input-icon text-xs"></i>
                                <input id="heures_travail" name="heures_travail" type="number" step="0.25" min="0"
                                       x-model="form.heures_travail"
                                       value="{{ old('heures_travail', $article->heures_travail ?? 1.0) }}"
                                       class="form-input text-xs bg-white"
                                       placeholder="ex. 1.5">
                            </div>
                        </div>

                        <div>
                            <label for="cout_materiaux" class="form-label text-xs">Coût matériaux (DT)</label>
                            <div class="input-wrap">
                                <i class="fas fa-cubes input-icon text-xs"></i>
                                <input id="cout_materiaux" name="cout_materiaux" type="number" step="0.01" min="0"
                                       x-model="form.cout_materiaux"
                                       value="{{ old('cout_materiaux', $article->cout_materiaux ?? 0.0) }}"
                                       class="form-input text-xs bg-white"
                                       placeholder="Fils, boutons, patchs…">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Panneau « Assistant d'estimation IA & Rentabilité » --}}
                <div class="border-2 border-purple-200 bg-gradient-to-b from-purple-50/40 via-white to-indigo-50/30 rounded-3xl p-6 sm:p-7 shadow-sm transition-all"
                     id="panneau-assistant-ia">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-purple-100">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-600 animate-pulse"></span>
                                <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Aide à la décision · Rôle Atelier</span>
                            </div>
                            <h3 class="text-lg font-bold font-display text-gray-900 flex items-center gap-2">
                                <i class="fas fa-sparkles text-purple-600"></i>
                                Assistant d'estimation des coûts, prix et rentabilité
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Répond à trois questions : combien me coûte la transformation, à quel prix vendre, et est-ce rentable ?
                            </p>
                        </div>
                        <button type="button"
                                @click="lancerEstimation()"
                                :disabled="loading"
                                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-700 hover:from-purple-700 hover:to-indigo-700 text-white font-bold text-xs shadow-md shadow-purple-600/20 hover:shadow-lg transition-all disabled:opacity-50 flex-shrink-0 cursor-pointer">
                            <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'"></i>
                            <span x-text="loading ? 'Analyse & Calcul en cours…' : (resultat ? 'Réestimer avec l\'IA' : 'Estimer avec l\'IA')"></span>
                        </button>
                    </div>

                    {{-- Erreur éventuelle --}}
                    <div x-show="error" x-cloak class="mt-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2">
                        <i class="fas fa-circle-exclamation text-red-500 text-sm"></i>
                        <span x-text="error"></span>
                    </div>

                    {{-- État initial avant estimation --}}
                    <div x-show="!resultat && !loading" class="py-8 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center mx-auto mb-3 text-xl">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-800">Prêt pour l'estimation</p>
                        <p class="text-xs text-gray-500 max-w-md mx-auto mt-1">
                            Renseignez les caractéristiques de votre pièce ci-dessus puis cliquez sur « Estimer avec l'IA » pour obtenir le détail des charges, la fourchette de prix et votre alerte de rentabilité.
                        </p>
                    </div>

                    {{-- Résultat de l'estimation --}}
                    <div x-show="resultat !== null" x-cloak class="mt-6 space-y-6">

                        {{-- 1. Grande alerte dynamique de rentabilité --}}
                        <div class="p-5 rounded-2xl border-2 transition-all"
                             :class="{
                                 'bg-emerald-50 border-emerald-300 text-emerald-950': alerteNiveauLive === 'vert',
                                 'bg-amber-50 border-amber-300 text-amber-950': alerteNiveauLive === 'orange',
                                 'bg-rose-50 border-rose-300 text-rose-950': alerteNiveauLive === 'rouge'
                             }">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-lg font-bold shadow-sm"
                                         :class="{
                                             'bg-emerald-500 text-white': alerteNiveauLive === 'vert',
                                             'bg-amber-500 text-white': alerteNiveauLive === 'orange',
                                             'bg-rose-500 text-white': alerteNiveauLive === 'rouge'
                                         }">
                                        <i class="fas"
                                           :class="{
                                               'fa-circle-check': alerteNiveauLive === 'vert',
                                               'fa-triangle-exclamation': alerteNiveauLive === 'orange',
                                               'fa-circle-xmark': alerteNiveauLive === 'rouge'
                                           }"></i>
                                    </div>
                                    <div>
                                        <span class="text-[11px] font-bold uppercase tracking-wider opacity-75">Statut de Rentabilité</span>
                                        <h4 class="text-lg font-extrabold" x-text="alerteTitreLive"></h4>
                                    </div>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="text-2xl font-black font-display"
                                       :class="{
                                           'text-emerald-700': alerteNiveauLive === 'vert',
                                           'text-amber-700': alerteNiveauLive === 'orange',
                                           'text-rose-700': alerteNiveauLive === 'rouge'
                                       }">
                                        <span x-text="margeNetteLive > 0 ? '+' + margeNetteLive.toFixed(2) : margeNetteLive.toFixed(2)"></span> DT
                                        <span class="text-xs font-semibold">(<span x-text="margePctLive.toFixed(1)"></span>%)</span>
                                    </p>
                                    <p class="text-[11px] opacity-75">Marge nette estimée</p>
                                </div>
                            </div>

                            {{-- Chiffres clés chiffrés --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-3 border-t border-black/10 text-xs">
                                <div class="bg-white/70 backdrop-blur-sm p-2.5 rounded-xl border border-black/5">
                                    <span class="text-gray-500 block text-[10px]">Coût total de revient</span>
                                    <strong class="text-gray-900 font-bold" x-text="resultat?.cout_upcycling?.toFixed(2) + ' DT'"></strong>
                                </div>
                                <div class="bg-white/70 backdrop-blur-sm p-2.5 rounded-xl border border-black/5">
                                    <span class="text-gray-500 block text-[10px]">Prix testé</span>
                                    <strong class="text-gray-900 font-bold" x-text="parseFloat(prixChoisi || 0).toFixed(2) + ' DT'"></strong>
                                </div>
                                <div class="bg-white/70 backdrop-blur-sm p-2.5 rounded-xl border border-black/5">
                                    <span class="text-gray-500 block text-[10px]">Frais plateforme</span>
                                    <strong class="text-gray-900 font-bold" x-text="(resultat?.parametres?.frais_plateforme_pct || 5) + '%'"></strong>
                                </div>
                                <div class="bg-white/70 backdrop-blur-sm p-2.5 rounded-xl border border-black/5">
                                    <span class="text-gray-500 block text-[10px]">Marge cible</span>
                                    <strong class="text-gray-900 font-bold" x-text="(resultat?.parametres?.marge_cible_pourcent || 20) + '%'"></strong>
                                </div>
                            </div>

                            {{-- Conseil personnalisé direct --}}
                            <div class="mt-3.5 pt-3 border-t border-black/10 flex items-start gap-2 text-xs font-medium">
                                <i class="fas fa-lightbulb text-amber-500 mt-0.5 flex-shrink-0"></i>
                                <span x-text="conseilLive"></span>
                            </div>
                        </div>

                        {{-- 2. Fourchette de prix & Curseur interactif --}}
                        <div class="p-5 bg-white rounded-2xl border border-purple-100 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                        <i class="fas fa-sliders text-purple-600"></i>
                                        Fourchette de revente conseillée
                                    </h4>
                                    <p class="text-xs text-gray-400">Ajustez le curseur pour simuler en temps réel l'impact sur votre marge.</p>
                                </div>
                                <button type="button"
                                        @click="appliquerPrixConseille()"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 font-semibold text-xs transition-colors self-start sm:self-auto cursor-pointer">
                                    <i class="fas fa-check-double text-purple-600"></i>
                                    <span>Utiliser le prix conseillé (<strong x-text="resultat?.prix_conseille + ' DT'"></strong>)</span>
                                </button>
                            </div>

                            {{-- Repères Min - Conseillé - Max --}}
                            <div class="grid grid-cols-3 gap-2 text-center mb-3">
                                <div class="p-2 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-[10px] text-gray-500 font-semibold uppercase">Prix plancher</p>
                                    <p class="text-sm font-bold text-gray-700" x-text="resultat?.prix_min + ' DT'"></p>
                                </div>
                                <div class="p-2 rounded-xl bg-purple-50 border border-purple-200">
                                    <p class="text-[10px] text-purple-700 font-bold uppercase">Prix conseillé IA</p>
                                    <p class="text-base font-extrabold text-purple-700" x-text="resultat?.prix_conseille + ' DT'"></p>
                                </div>
                                <div class="p-2 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-[10px] text-gray-500 font-semibold uppercase">Prix plafond</p>
                                    <p class="text-sm font-bold text-gray-700" x-text="resultat?.prix_max + ' DT'"></p>
                                </div>
                            </div>

                            {{-- Curseur interactif (Range Slider) --}}
                            <div class="space-y-2 mt-4 px-1">
                                <div class="flex items-center justify-between text-xs text-gray-400">
                                    <span x-text="sliderMin + ' DT'"></span>
                                    <span class="font-bold text-purple-700 text-sm" x-text="'Prix sélectionné : ' + parseFloat(prixChoisi || 0).toFixed(2) + ' DT'"></span>
                                    <span x-text="sliderMax + ' DT'"></span>
                                </div>
                                <input type="range"
                                       :min="sliderMin"
                                       :max="sliderMax"
                                       step="0.5"
                                       x-model="prixChoisi"
                                       @input="recalculerLive()"
                                       class="w-full h-2.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-purple-600">
                            </div>
                        </div>

                        {{-- 3. Détail ligne par ligne des charges (Recyclage vs Upcycling) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            {{-- Filière Upcycling --}}
                            <div class="p-4 rounded-2xl bg-white border border-amber-200/80 shadow-sm">
                                <div class="flex items-center justify-between mb-3 pb-2 border-b border-amber-100">
                                    <h5 class="text-xs font-bold text-amber-900 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="fas fa-cut text-amber-600"></i> Filière Upcycling
                                    </h5>
                                    <strong class="text-sm font-extrabold text-amber-700" x-text="resultat?.detail_upcycling?.total?.toFixed(2) + ' DT'"></strong>
                                </div>
                                <div class="space-y-1.5 text-xs text-gray-600">
                                    <div class="flex justify-between">
                                        <span>Achat de la pièce :</span>
                                        <span class="font-medium text-gray-800" x-text="resultat?.detail_upcycling?.achat?.toFixed(2) + ' DT'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Main-d'œuvre (<span x-text="resultat?.detail_upcycling?.heures + 'h × ' + resultat?.detail_upcycling?.tarif_horaire + ' DT/h'"></span>) :</span>
                                        <span class="font-medium text-gray-800" x-text="resultat?.detail_upcycling?.main_d_oeuvre?.toFixed(2) + ' DT'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Matériaux / fournitures :</span>
                                        <span class="font-medium text-gray-800" x-text="resultat?.detail_upcycling?.materiaux?.toFixed(2) + ' DT'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Transport & logistique :</span>
                                        <span class="font-medium text-gray-800" x-text="resultat?.detail_upcycling?.transport?.toFixed(2) + ' DT'"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Filière Recyclage --}}
                            <div class="p-4 rounded-2xl bg-white border border-blue-200/80 shadow-sm">
                                <div class="flex items-center justify-between mb-3 pb-2 border-b border-blue-100">
                                    <h5 class="text-xs font-bold text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="fas fa-recycle text-blue-600"></i> Filière Recyclage
                                    </h5>
                                    <strong class="text-sm font-extrabold text-blue-700" x-text="resultat?.detail_recyclage?.total?.toFixed(2) + ' DT'"></strong>
                                </div>
                                <div class="space-y-1.5 text-xs text-gray-600">
                                    <div class="flex justify-between">
                                        <span>Tri fixe unitaire :</span>
                                        <span class="font-medium text-gray-800" x-text="resultat?.detail_recyclage?.tri?.toFixed(2) + ' DT'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Traitement matière (<span x-text="resultat?.detail_recyclage?.cout_au_kg + ' DT/kg'"></span>) :</span>
                                        <span class="font-medium text-gray-800" x-text="resultat?.detail_recyclage?.traitement_base?.toFixed(2) + ' DT'"></span>
                                    </div>
                                    <div class="flex justify-between" x-show="resultat?.detail_recyclage?.majoration_mixte > 0">
                                        <span>Majoration mélange complexe (<span x-text="resultat?.detail_recyclage?.majoration_pourcent + '%'"></span>) :</span>
                                        <span class="font-medium text-amber-600" x-text="resultat?.detail_recyclage?.majoration_mixte?.toFixed(2) + ' DT'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Transport par poids :</span>
                                        <span class="font-medium text-gray-800" x-text="resultat?.detail_recyclage?.transport?.toFixed(2) + ' DT'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Orientation recommandée --}}
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3 text-xs">
                            <span class="px-2 py-0.5 rounded-md font-bold text-[10px]"
                                  :class="resultat?.filiere_orientation?.badge_class"
                                  x-text="resultat?.filiere_orientation?.filiere_recommandee"></span>
                            <p class="text-slate-600" x-text="resultat?.filiere_orientation?.motif"></p>
                        </div>

                        {{-- 4. Explication & Indice de confiance IA --}}
                        <div class="p-4 rounded-2xl bg-purple-50/60 border border-purple-100 text-xs">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 font-bold px-2 py-0.5 rounded-full text-[10px]"
                                          :class="resultat?.source === 'ia' ? 'bg-purple-200 text-purple-800' : 'bg-blue-100 text-blue-800'">
                                        <i class="fas" :class="resultat?.source === 'ia' ? 'fa-sparkles' : 'fa-calculator'"></i>
                                        <span x-text="resultat?.source === 'ia' ? 'IA RETISS Hybride' : 'Règles & Barèmes métier'"></span>
                                    </span>
                                    <span class="text-gray-400">·</span>
                                    <span class="text-gray-500 text-[11px]">
                                        Confiance : <strong class="text-gray-700 capitalize" x-text="resultat?.confiance"></strong>
                                    </span>
                                </div>
                            </div>
                            <p class="text-slate-700 leading-relaxed italic" x-text="'« ' + resultat?.explication + ' »'"></p>
                        </div>

                    </div>{{-- /resultat --}}

                </div>{{-- /panneau-assistant-ia --}}
                @endif

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
                                       x-model="form.prix"
                                       @input="onManualPriceInput()"
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

// ── Assistant d'estimation IA & Rentabilité (Alpine.js) ────────────────
function assistantEstimation(config) {
    return {
        articleId: config.articleId,
        isAtelier: config.isAtelier,
        form: {
            titre: '',
            categorie: config.initialCategorie || '',
            matiere: config.initialMatiere || '',
            taille: config.initialTaille || '',
            etat: config.initialEtat || '',
            couleur: config.initialCouleur || '',
            poids_kg: config.initialPoids || 0.5,
            cout_achat: config.initialAchat || 0,
            heures_travail: config.initialHeures || 1,
            cout_materiaux: config.initialMateriaux || 0,
            prix: config.initialPrix || '',
        },
        loading: false,
        error: null,
        resultat: null,
        prixChoisi: config.initialPrix ? parseFloat(config.initialPrix) : 0,
        sliderMin: 5,
        sliderMax: 100,
        margeNetteLive: 0,
        margePctLive: 0,
        alerteNiveauLive: 'vert',
        alerteTitreLive: '',
        conseilLive: '',

        init() {
            const titreInput = document.getElementById('titre');
            if (titreInput) {
                this.form.titre = titreInput.value;
                titreInput.addEventListener('input', (e) => { this.form.titre = e.target.value; });
            }
            const catSelect = document.getElementById('categorie');
            if (catSelect) {
                this.form.categorie = catSelect.value;
                catSelect.addEventListener('change', (e) => { this.form.categorie = e.target.value; });
            }
        },

        async lancerEstimation() {
            this.loading = true;
            this.error = null;

            this.form.titre = document.getElementById('titre')?.value || this.form.titre;
            this.form.categorie = document.getElementById('categorie')?.value || this.form.categorie;

            const payload = {
                titre: this.form.titre,
                categorie: this.form.categorie,
                matiere: this.form.matiere,
                taille: this.form.taille,
                etat: this.form.etat,
                couleur: this.form.couleur,
                poids_kg: parseFloat(this.form.poids_kg) || 0.5,
                cout_achat: parseFloat(this.form.cout_achat) || 0,
                heures_travail: parseFloat(this.form.heures_travail) || 0,
                cout_materiaux: parseFloat(this.form.cout_materiaux) || 0,
                prix: parseFloat(this.form.prix) || null,
                article_id: this.articleId,
                save: true,
                _token: config.csrfToken
            };

            try {
                const response = await axios.post(config.endpointCalculer, payload);
                if (response.data && response.data.success) {
                    this.resultat = response.data.data;
                    
                    const conseillle = parseFloat(this.resultat.prix_conseille) || 30;
                    const pMin = parseFloat(this.resultat.prix_min) || (conseillle * 0.8);
                    const pMax = parseFloat(this.resultat.prix_max) || (conseillle * 1.25);
                    
                    this.sliderMin = Math.max(2, Math.floor(pMin * 0.7));
                    this.sliderMax = Math.ceil(pMax * 1.3);

                    if (!this.form.prix || parseFloat(this.form.prix) <= 0) {
                        this.prixChoisi = conseillle;
                        this.form.prix = conseillle.toFixed(2);
                    } else {
                        this.prixChoisi = parseFloat(this.form.prix);
                    }

                    this.recalculerLive();

                    this.$nextTick(() => {
                        const panel = document.getElementById('panneau-assistant-ia');
                        if (panel) {
                            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    });
                } else {
                    this.error = response.data?.error || "Une erreur est survenue lors du calcul.";
                }
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.message || err.response?.data?.error || "Erreur lors de la communication avec l'assistant d'estimation.";
            } finally {
                this.loading = false;
            }
        },

        recalculerLive() {
            if (!this.resultat) return;

            const prix = parseFloat(this.prixChoisi) || 0;
            const coutRevient = parseFloat(this.resultat.cout_upcycling) || 0;
            const coutRecyclage = parseFloat(this.resultat.cout_recyclage) || 0;
            const fraisPct = parseFloat(this.resultat.parametres?.frais_plateforme_pct) || 5;
            const margeCiblePct = parseFloat(this.resultat.parametres?.marge_cible_pourcent) || 20;

            const frais = (prix * (fraisPct / 100));
            const margeNette = prix - coutRevient - frais;
            const margePct = prix > 0 ? (margeNette / prix) * 100 : -100;

            this.margeNetteLive = margeNette;
            this.margePctLive = margePct;

            this.form.prix = prix.toFixed(2);

            const denom = 1 - ((fraisPct + margeCiblePct) / 100);
            const prixPourCible = denom > 0 ? (coutRevient / denom).toFixed(2) : (coutRevient * 1.3).toFixed(2);

            if (margeNette > 0 && margePct >= margeCiblePct) {
                this.alerteNiveauLive = 'vert';
                this.alerteTitreLive = 'Article gagnant (Rentabilité optimale)';
                this.conseilLive = `Excellente rentabilité ! Votre marge nette de ${margeNette.toFixed(2)} DT (${margePct.toFixed(1)}%) dépasse l'objectif cible de ${margeCiblePct}%.`;
            } else if (margeNette > 0 && margePct < margeCiblePct) {
                this.alerteNiveauLive = 'orange';
                this.alerteTitreLive = 'À surveiller (Marge positive mais faible)';
                this.conseilLive = `Marge positive mais faible (${margeNette.toFixed(2)} DT, soit ${margePct.toFixed(1)}%). Augmentez le prix à ${prixPourCible} DT pour atteindre l'objectif de ${margeCiblePct}% de marge.`;
            } else {
                this.alerteNiveauLive = 'rouge';
                this.alerteTitreLive = 'Article perdant (Vente à perte)';
                const perte = Math.abs(margeNette).toFixed(2);
                if (coutRevient > prix && coutRevient > (coutRecyclage * 2.5) && coutRecyclage > 0) {
                    this.conseilLive = `Attention, vente à perte (déficit de ${perte} DT). Cet article coûte plus cher à transformer (${coutRevient.toFixed(2)} DT) qu'à recycler (${coutRecyclage.toFixed(2)} DT) : orientez plutôt cette pièce vers la filière recyclage ou fixez un prix minimal de ${prixPourCible} DT.`;
                } else {
                    this.conseilLive = `Attention, marge déficitaire (-${perte} DT). Pour couvrir vos charges d'atelier et atteindre ${margeCiblePct}% de marge, le prix minimal requis est de ${prixPourCible} DT.`;
                }
            }
        },

        onManualPriceInput() {
            const p = parseFloat(this.form.prix);
            if (!isNaN(p) && p >= 0) {
                this.prixChoisi = p;
                if (p > this.sliderMax) this.sliderMax = Math.ceil(p * 1.2);
                if (p < this.sliderMin) this.sliderMin = Math.max(1, Math.floor(p * 0.8));
                this.recalculerLive();
            }
        },

        appliquerPrixConseille() {
            if (this.resultat?.prix_conseille) {
                this.prixChoisi = parseFloat(this.resultat.prix_conseille);
                this.recalculerLive();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Prix conseillé (${this.prixChoisi.toFixed(2)} DT) appliqué !`,
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            }
        }
    };
}
</script>
@endsection
