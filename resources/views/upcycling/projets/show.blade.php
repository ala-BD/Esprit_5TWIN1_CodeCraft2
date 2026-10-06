@extends('upcycling.layouts.upcycling')

@section('title', $projet->produit_final ?? 'Projet #' . $projet->id)

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.projets.index') }}" class="hover:text-primary transition-colors">Projets</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Projet #{{ $projet->id }}</span>
@endsection

@php
    $badge        = $projet->statut_badge;
    $devisAttente = $projet->devis_en_attente;
    $annule       = $projet->statut === 'ANNULE';
    $ideeRetenue  = $projet->idee_retenue;
    $defauts      = $projet->analyse_ia['defauts'] ?? [];
    $peutChoisir  = $estProprietaire && $projet->estModifiable();
    $difficultes  = [
        'FACILE'    => ['Facile', 'bg-emerald-100 text-emerald-700', 1],
        'MOYEN'     => ['Moyen', 'bg-amber-100 text-amber-700', 2],
        'DIFFICILE' => ['Difficile', 'bg-rose-100 text-rose-700', 3],
    ];
@endphp

@section('upcycling-content')

{{-- ===================== EN-TÊTE ===================== --}}
<div class="grid lg:grid-cols-5 gap-6 mb-6">

    {{-- Photos avant / après --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            @if($projet->photo_resultat_url)
                <div class="grid grid-cols-2 divide-x divide-gray-100">
                    @foreach([['Avant', $projet->photo_url, 'bg-gray-900/70'], ['Après', $projet->photo_resultat_url, 'bg-primary']] as [$label, $url, $couleur])
                        <div class="relative aspect-[3/4] bg-gradient-to-br from-slate-50 to-slate-100 p-4">
                            @include('upcycling.partials.photo', ['url' => $url, 'alt' => $label])
                            <span class="absolute top-3 left-3 text-[11px] font-bold text-white px-2.5 py-1 rounded-lg {{ $couleur }}">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="relative aspect-square bg-gradient-to-br from-slate-50 to-slate-100 p-6">
                    @include('upcycling.partials.photo', ['url' => $projet->photo_url, 'alt' => $projet->type_vetement])
                    <span class="absolute top-4 left-4 text-[11px] font-bold text-white px-2.5 py-1 rounded-lg bg-gray-900/70">Avant</span>
                    @if($estProprietaire && !$projet->photo_url && $projet->estModifiable())
                        <a href="{{ route('upcycling.projets.edit', $projet) }}"
                           class="absolute bottom-4 left-1/2 -translate-x-1/2 text-xs font-semibold bg-white shadow px-3 py-2 rounded-xl text-primary whitespace-nowrap">
                            <i class="fas fa-camera mr-1"></i> Ajouter une photo
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Informations --}}
    <div class="lg:col-span-3 flex flex-col gap-5">
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="px-3 py-1 rounded-lg text-xs font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                @if($projet->categorie_produit)
                    <span class="px-3 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-600">
                        <i class="fas {{ \App\Models\Atelier::ICONS[$projet->categorie_produit] }} mr-1"></i>{{ \App\Models\Atelier::SPECIALITES[$projet->categorie_produit] }}
                    </span>
                @endif
            </div>

            <h1 class="font-display text-2xl lg:text-3xl font-extrabold text-gray-900 leading-tight">
                {{ $projet->produit_final ?? 'Choisissez une idée de transformation' }}
            </h1>
            <p class="text-gray-500 mt-1 text-sm">Demandé par {{ $projet->client->full_name }} le {{ $projet->created_at->format('d/m/Y') }}</p>

            {{-- Fiche vêtement --}}
            <div class="flex flex-wrap gap-2 mt-5">
                @foreach([
                    ['fa-tag', $projet->type_vetement],
                    ['fa-layer-group', $projet->matiere],
                    ['fa-palette', $projet->couleur],
                    ['fa-heart-pulse', \App\Models\ProjetUpcycling::ETATS[$projet->etat] ?? $projet->etat],
                    ['fa-wallet', $projet->budget_max ? 'Budget ' . number_format($projet->budget_max, 0) . ' DT' : null],
                ] as [$icone, $texte])
                    @if($texte)
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-700">
                            <i class="fas {{ $icone }} text-slate-400"></i> {{ $texte }}
                        </span>
                    @endif
                @endforeach
            </div>

            <p class="mt-4 text-sm text-gray-600 leading-relaxed border-l-4 border-primary/30 pl-4 italic">{{ $projet->description }}</p>

            @if(count($defauts))
                <div class="mt-4 flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-semibold text-gray-500 mr-1"><i class="fas fa-magnifying-glass text-violet-400"></i> Défauts repérés par l'IA :</span>
                    @foreach($defauts as $defaut)
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">{{ $defaut }}</span>
                    @endforeach
                </div>
            @endif

            <div class="grid sm:grid-cols-3 gap-3 mt-5 pt-5 border-t border-gray-100 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Atelier</p>
                    @if($projet->atelier)
                        <a href="{{ route('upcycling.ateliers.show', $projet->atelier) }}" class="font-semibold text-gray-800 hover:text-primary">{{ $projet->atelier->nom }}</a>
                    @else
                        <p class="font-semibold text-gray-400">À choisir</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-400">Estimation IA</p>
                    <p class="font-semibold text-gray-800">
                        {{ $projet->prix_estime_min ? number_format($projet->prix_estime_min, 0) . ' – ' . number_format($projet->prix_estime_max, 0) . ' DT' : '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">{{ $projet->date_fin ? 'Terminé le' : 'Début des travaux' }}</p>
                    <p class="font-semibold text-gray-800">{{ ($projet->date_fin ?? $projet->date_debut)?->format('d/m/Y') ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Impact --}}
        @if($projet->co2_evite_kg)
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-3xl p-5 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-100">
                <p class="text-xs text-white/70 font-semibold uppercase tracking-wider"><i class="fas fa-leaf mr-1"></i> CO₂ évité</p>
                <p class="text-3xl font-extrabold mt-1">≈ {{ number_format($projet->co2_evite_kg, 0) }} <span class="text-lg">kg</span></p>
            </div>
            <div class="rounded-3xl p-5 bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-100">
                <p class="text-xs text-white/70 font-semibold uppercase tracking-wider"><i class="fas fa-tint mr-1"></i> Eau économisée</p>
                <p class="text-3xl font-extrabold mt-1">≈ {{ number_format($projet->eau_economisee_l, 0, ',', ' ') }} <span class="text-lg">L</span></p>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ===================== SUIVI PAR ÉTAPES ===================== --}}
@unless($annule)
<div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="font-display font-bold text-gray-900 flex items-center gap-2">
            <i class="fas fa-route text-primary"></i> Suivi du projet
        </h2>
        <span class="text-sm font-bold text-primary bg-primary/10 px-3 py-1 rounded-full">{{ $projet->progression }}%</span>
    </div>

    <div class="grid grid-cols-7 gap-1">
        @foreach(\App\Models\ProjetUpcycling::ETAPES as $code => $etape)
            @php
                $faite    = $loop->index < $projet->index_etape || $projet->statut === 'TERMINE';
                $courante = $loop->index === $projet->index_etape && $projet->statut !== 'TERMINE';
            @endphp
            <div class="flex flex-col items-center text-center">
                <div class="relative w-full flex items-center justify-center">
                    @unless($loop->first)
                        <div class="absolute right-1/2 w-full h-1 rounded {{ $faite || $courante ? 'bg-gradient-to-r from-primary to-accent' : 'bg-gray-100' }}"></div>
                    @endunless
                    <div class="relative z-10 w-11 h-11 rounded-2xl flex items-center justify-center transition-all
                        {{ $faite ? 'bg-gradient-to-br from-primary to-accent-dark shadow-md' : ($courante ? 'bg-white border-2 border-primary shadow-lg ring-4 ring-primary/10' : 'bg-gray-50 border border-gray-200') }}">
                        <i class="fas {{ $faite ? 'fa-check' : $etape['icon'] }} text-sm {{ $faite ? 'text-white' : ($courante ? 'text-primary' : 'text-gray-300') }}"></i>
                    </div>
                </div>
                <p class="mt-2 text-[10px] sm:text-xs font-semibold {{ $faite ? 'text-gray-700' : ($courante ? 'text-primary' : 'text-gray-400') }}">{{ $etape['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Action de l'atelier --}}
    @if($estAtelierProjet && $projet->etape_suivante)
        @php $libelle = \App\Models\ProjetUpcycling::ETAPES[$projet->etape_suivante]['label']; @endphp
        <form method="POST" action="{{ route('upcycling.projets.avancer', $projet) }}" enctype="multipart/form-data" novalidate
              class="mt-6 pt-6 border-t border-gray-100 flex flex-col md:flex-row md:items-center gap-4">
            @csrf @method('PATCH')
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-800">Étape suivante : {{ $libelle }}</p>
                @if($projet->etape_suivante === 'TERMINE')
                    <p class="text-xs text-gray-500 mt-0.5">Ajoutez une photo du produit fini : elle apparaîtra dans votre portfolio (avant / après).</p>
                    <input type="file" name="photo_resultat" accept="image/jpeg,image/png,image/webp"
                           class="mt-2 block text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-primary/10 file:text-primary file:font-semibold">
                    @error('photo_resultat')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                @endif
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-forward"></i> Valider « {{ $libelle }} »
            </button>
        </form>
    @endif
</div>
@else
<div class="bg-gray-100 border border-gray-200 rounded-3xl p-5 mb-6 flex items-center gap-3 text-gray-600 text-sm">
    <i class="fas fa-ban"></i> Ce projet a été annulé.
</div>
@endunless

{{-- ===================== IDÉES DE L'IA ===================== --}}
<section class="mb-6">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 mb-5">
        <div>
            <h2 class="font-display text-xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-lightbulb text-amber-400"></i> Idées de transformation
                @if($projet->source_ia === 'GEMINI')
                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-gradient-to-r from-violet-600 to-pink-500 text-white">
                        <i class="fas fa-wand-magic-sparkles"></i> Gemini
                    </span>
                @else
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-500" title="Clé GEMINI_API_KEY absente : générateur local">Générateur local</span>
                @endif
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $peutChoisir ? 'Choisissez l\'idée qui vous plaît, ou demandez à l\'IA une variante.' : 'Propositions générées pour ce vêtement.' }}
            </p>
        </div>

        @if($peutChoisir)
            <form method="POST" action="{{ route('upcycling.projets.idees', $projet) }}" class="flex gap-2 w-full lg:w-auto"
                  onsubmit="const b=this.querySelector('button'); b.disabled=true; b.innerHTML='<i class=\'fas fa-circle-notch fa-spin\'></i> Génération…';">
                @csrf
                <input type="text" name="consigne" maxlength="200" value="{{ old('consigne') }}"
                       placeholder="Ex : pour un enfant, plus coloré, sans couture…"
                       class="flex-1 lg:w-80 px-4 py-2.5 rounded-xl border {{ $errors->has('consigne') ? 'border-red-400' : 'border-gray-200' }} focus:outline-none focus:ring-2 focus:ring-violet-400 text-sm">
                <button class="flex-shrink-0 inline-flex items-center gap-2 text-sm font-semibold text-white px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-pink-500 hover:shadow-lg transition-all disabled:opacity-60">
                    <i class="fas fa-wand-magic-sparkles"></i> Autres idées
                </button>
            </form>
        @endif
    </div>
    @error('consigne')<p class="-mt-3 mb-3 text-xs text-red-500">{{ $message }}</p>@enderror

    <div class="grid md:grid-cols-3 gap-5">
        @foreach($projet->idee_generee_ia ?? [] as $index => $idee)
            @php
                $choisie = $projet->produit_final === $idee['titre'];
                [$diffLabel, $diffClasse, $niveau] = $difficultes[$idee['difficulte']] ?? ['Moyen', 'bg-gray-100 text-gray-600', 2];
            @endphp
            <article class="relative bg-white rounded-3xl border-2 p-6 flex flex-col transition-all duration-300
                            {{ $choisie ? 'border-primary shadow-xl shadow-primary/10' : 'border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-0.5' }}">
                @if($choisie)
                    <span class="absolute -top-3 left-6 text-[11px] font-bold text-white bg-primary px-3 py-1 rounded-full shadow">
                        <i class="fas fa-check-circle"></i> Idée retenue
                    </span>
                @endif

                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary/10 to-accent/20 flex items-center justify-center">
                        <i class="fas {{ \App\Models\Atelier::ICONS[$idee['categorie']] ?? 'fa-cut' }} text-primary text-lg"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-extrabold text-gray-900">{{ $idee['prix_min'] ?? '?' }}–{{ $idee['prix_max'] ?? '?' }} <span class="text-xs font-semibold text-gray-500">DT</span></p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">prix estimé</p>
                    </div>
                </div>

                <h3 class="font-display font-bold text-gray-900 text-lg leading-snug">{{ $idee['titre'] }}</h3>

                <div class="flex flex-wrap gap-1.5 mt-2">
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $diffClasse }}">
                        @for($i = 1; $i <= 3; $i++)<i class="fas fa-circle text-[6px] align-middle {{ $i <= $niveau ? '' : 'opacity-30' }}"></i>@endfor
                        {{ $diffLabel }}
                    </span>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600"><i class="far fa-clock"></i> {{ $idee['duree_heures'] }} h</span>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ \App\Models\Atelier::SPECIALITES[$idee['categorie']] ?? $idee['categorie'] }}</span>
                </div>

                <p class="text-sm text-gray-600 leading-relaxed mt-4">{{ $idee['description'] }}</p>

                @if(!empty($idee['etapes']))
                    <details class="mt-4 group/etapes">
                        <summary class="cursor-pointer text-xs font-semibold text-primary list-none flex items-center gap-1">
                            <i class="fas fa-chevron-right text-[10px] transition-transform group-open/etapes:rotate-90"></i>
                            Étapes de fabrication ({{ count($idee['etapes']) }})
                        </summary>
                        <ol class="mt-3 space-y-2">
                            @foreach($idee['etapes'] as $n => $etape)
                                <li class="flex gap-2 text-xs text-gray-600">
                                    <span class="w-5 h-5 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center flex-shrink-0 text-[10px]">{{ $n + 1 }}</span>
                                    <span class="pt-0.5">{{ $etape }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif

                @if(!empty($idee['materiaux']))
                    <div class="flex flex-wrap gap-1.5 mt-4">
                        @foreach($idee['materiaux'] as $materiau)
                            <span class="text-[11px] bg-amber-50 text-amber-800 px-2 py-0.5 rounded-md"><i class="fas fa-plus text-[8px] mr-0.5"></i> {{ $materiau }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="mt-auto pt-5">
                    @if($peutChoisir && !$choisie)
                        <form method="POST" action="{{ route('upcycling.projets.idee', $projet) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="index" value="{{ $index }}">
                            <button class="w-full text-sm font-bold py-3 rounded-2xl border-2 border-primary text-primary hover:bg-primary hover:text-white transition-colors">
                                Choisir cette idée
                            </button>
                        </form>
                    @elseif($choisie && $estProprietaire && !$projet->atelier_id && !$annule)
                        <a href="{{ route('upcycling.projets.matching', $projet) }}" class="btn-primary w-full !rounded-2xl">
                            <i class="fas fa-bullseye"></i> Trouver un atelier
                        </a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</section>

{{-- ===================== ATELIER, DEVIS, AVIS ===================== --}}
@php $colonneDroite = $projet->atelier_id || $projet->devis->isNotEmpty() || $projet->statut === 'TERMINE'; @endphp
<div class="grid {{ $colonneDroite ? 'lg:grid-cols-3' : '' }} gap-6">

    <div class="{{ $colonneDroite ? 'space-y-5' : 'grid md:grid-cols-2 gap-5 items-start' }}">
        {{-- Atelier --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            @if($projet->atelier)
                <div class="h-24 bg-gradient-to-br from-slate-50 to-slate-100 relative">
                    @if($projet->atelier->couverture_url)
                        <img src="{{ $projet->atelier->couverture_url }}" alt="" class="w-full h-full object-contain p-2 opacity-90">
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Atelier</p>
                    <a href="{{ route('upcycling.ateliers.show', $projet->atelier) }}" class="font-display font-bold text-gray-900 hover:text-primary">{{ $projet->atelier->nom }}</a>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $projet->atelier->specialite_label }} · {{ $projet->atelier->localisation }}
                        @if($projet->atelier->note_moyenne > 0) · <i class="fas fa-star text-amber-400"></i> {{ number_format($projet->atelier->note_moyenne, 1) }} @endif
                    </p>
                    @if($estProprietaire && $projet->statut === 'ATELIER_CHOISI')
                        <a href="{{ route('upcycling.projets.matching', $projet) }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-primary">
                            <i class="fas fa-exchange-alt"></i> Changer d'atelier
                        </a>
                    @endif
                </div>
            @else
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-3"><i class="fas fa-store text-primary"></i></div>
                    <p class="text-sm text-gray-500">
                        {{ $projet->produit_final ? 'Idée choisie : trouvez l\'atelier qui la réalisera.' : 'Choisissez d\'abord une idée ci-dessus.' }}
                    </p>
                    @if($estProprietaire && $projet->produit_final && !$annule)
                        <a href="{{ route('upcycling.projets.matching', $projet) }}" class="btn-primary w-full mt-4 !py-3">
                            <i class="fas fa-bullseye"></i> Matching atelier
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Actions client --}}
        @if($estProprietaire && ($projet->estModifiable() || $projet->estAnnulable() || in_array($projet->statut, ['DEMANDE', 'ANNULE'])))
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 space-y-2">
            @if($projet->estModifiable())
                <a href="{{ route('upcycling.projets.edit', $projet) }}"
                   class="flex items-center gap-3 w-full bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold px-4 py-3 rounded-2xl transition-all text-sm">
                    <i class="fas fa-pen w-4"></i> Modifier la demande
                </a>
            @endif
            @if($projet->estAnnulable())
                <form method="POST" action="{{ route('upcycling.projets.annuler', $projet) }}" onsubmit="return confirm('Annuler ce projet ?')">
                    @csrf @method('PATCH')
                    <button class="flex items-center gap-3 w-full bg-gray-50 hover:bg-gray-100 text-gray-600 font-semibold px-4 py-3 rounded-2xl transition-all text-sm">
                        <i class="fas fa-ban w-4"></i> Annuler le projet
                    </button>
                </form>
            @endif
            @if(in_array($projet->statut, ['DEMANDE', 'ANNULE']))
                <form method="POST" action="{{ route('upcycling.projets.destroy', $projet) }}" onsubmit="return confirm('Supprimer définitivement cette demande ?')">
                    @csrf @method('DELETE')
                    <button class="flex items-center gap-3 w-full bg-red-50 hover:bg-red-100 text-red-600 font-semibold px-4 py-3 rounded-2xl transition-all text-sm">
                        <i class="fas fa-trash-alt w-4"></i> Supprimer
                    </button>
                </form>
            @endif
        </div>
        @endif
    </div>

    <div class="{{ $colonneDroite ? 'lg:col-span-2 space-y-5' : 'hidden' }}">
        {{-- Devis --}}
        @if($projet->atelier_id || $projet->devis->isNotEmpty())
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-display font-bold text-gray-900 mb-5 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-primary"></i> Devis
            </h2>

            @if($estAtelierProjet && $projet->statut === 'ATELIER_CHOISI' && !$devisAttente)
                <form method="POST" action="{{ route('upcycling.devis.store', $projet) }}" class="rounded-2xl bg-slate-50 p-5 mb-5 space-y-4" novalidate>
                    @csrf
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-bold text-gray-800">Proposer un devis</p>
                        <div class="flex flex-wrap gap-2 text-[11px] font-semibold">
                            @if($projet->prix_estime_min)
                                <span class="px-2.5 py-1 rounded-full bg-violet-100 text-violet-700"><i class="fas fa-wand-magic-sparkles"></i> Estimation IA : {{ number_format($projet->prix_estime_min, 0) }}–{{ number_format($projet->prix_estime_max, 0) }} DT</span>
                            @endif
                            @if($projet->budget_max)
                                <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">Budget client : {{ number_format($projet->budget_max, 0) }} DT</span>
                            @endif
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Montant (DT) <span class="text-red-500">*</span></label>
                            <input type="number" name="montant" step="0.5" min="1" value="{{ old('montant', $projet->prix_estime_min) }}"
                                   class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('montant') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                            @error('montant')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Délai (jours) <span class="text-red-500">*</span></label>
                            <input type="number" name="delai_jours" min="1" value="{{ old('delai_jours') }}"
                                   class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('delai_jours') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                            @error('delai_jours')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <textarea name="message" rows="2" placeholder="Détail de la prestation : travaux, fournitures incluses…"
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm resize-none">{{ old('message') }}</textarea>
                    <button class="btn-primary !py-2.5"><i class="fas fa-paper-plane"></i> Envoyer le devis</button>
                </form>
            @elseif($projet->statut === 'ATELIER_CHOISI' && !$devisAttente && $estProprietaire)
                <div class="flex items-center gap-3 rounded-2xl bg-sky-50 text-sky-700 p-4 mb-5 text-sm">
                    <i class="fas fa-hourglass-half animate-pulse"></i> En attente du devis de l'atelier.
                </div>
            @endif

            <div class="space-y-3">
                @forelse($projet->devis as $devis)
                    @php $badgeDevis = $devis->statut_badge; @endphp
                    <div class="rounded-2xl border p-4 {{ $devis->statut === 'EN_ATTENTE' ? 'border-amber-200 bg-amber-50/40' : 'border-gray-100' }}">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-4">
                                <p class="text-2xl font-extrabold text-gray-900">{{ number_format($devis->montant, 0, ',', ' ') }} <span class="text-sm font-semibold text-gray-500">DT</span></p>
                                <div class="text-xs text-gray-500">
                                    <p><i class="far fa-clock"></i> {{ $devis->delai_jours }} jour(s)</p>
                                    <p>Émis le {{ $devis->date_emission->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $badgeDevis['class'] }}">{{ $badgeDevis['label'] }}</span>
                                @if($estProprietaire && $devis->statut === 'EN_ATTENTE')
                                    <form method="POST" action="{{ route('upcycling.devis.accepter', $devis) }}">
                                        @csrf @method('PATCH')
                                        <button class="text-xs font-bold bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl transition-colors"><i class="fas fa-check"></i> Accepter</button>
                                    </form>
                                    <form method="POST" action="{{ route('upcycling.devis.refuser', $devis) }}" onsubmit="return confirm('Refuser ce devis ?')">
                                        @csrf @method('PATCH')
                                        <button class="text-xs font-bold bg-white border border-red-200 hover:bg-red-50 text-red-600 px-4 py-2 rounded-xl transition-colors"><i class="fas fa-times"></i> Refuser</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if($devis->message)<p class="text-sm text-gray-600 mt-3">{{ $devis->message }}</p>@endif
                        @if($devis->statut === 'EN_ATTENTE' && $projet->budget_max && $devis->montant > $projet->budget_max)
                            <p class="text-xs text-amber-700 mt-2"><i class="fas fa-exclamation-triangle"></i> Dépasse le budget indiqué ({{ number_format($projet->budget_max, 0) }} DT).</p>
                        @endif
                    </div>
                @empty
                    @if($estAtelierProjet)
                        <p class="text-sm text-gray-400">Aucun devis envoyé.</p>
                    @endif
                @endforelse
            </div>
        </div>
        @endif

        {{-- Avis --}}
        @if($projet->statut === 'TERMINE')
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-display font-bold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-star text-amber-400"></i> Avis du client</h2>

            @if($projet->note_client)
                <div class="flex items-center gap-1 mb-2">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star text-lg {{ $i <= $projet->note_client ? 'text-amber-400' : 'text-gray-200' }}"></i>
                    @endfor
                    <span class="ml-2 text-sm font-bold text-gray-700">{{ $projet->note_client }}/5</span>
                </div>
                @if($projet->commentaire_client)
                    <p class="text-gray-600 italic">« {{ $projet->commentaire_client }} »</p>
                @endif
            @elseif($estProprietaire)
                <form method="POST" action="{{ route('upcycling.projets.noter', $projet) }}" class="space-y-4" novalidate>
                    @csrf @method('PATCH')
                    <div class="flex flex-row-reverse justify-end gap-1">
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" id="note-{{ $i }}" name="note_client" value="{{ $i }}" class="peer sr-only" @checked(old('note_client') == $i)>
                            <label for="note-{{ $i }}" class="cursor-pointer text-3xl text-gray-200 hover:text-amber-400 peer-checked:text-amber-400 transition-colors">
                                <i class="fas fa-star"></i>
                            </label>
                        @endfor
                    </div>
                    @error('note_client')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                    <textarea name="commentaire_client" rows="2" placeholder="Votre avis sur le travail de l'atelier…"
                              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm resize-none">{{ old('commentaire_client') }}</textarea>
                    <button class="inline-flex items-center gap-2 bg-amber-400 hover:bg-amber-500 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-colors">
                        <i class="fas fa-paper-plane"></i> Publier mon avis
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-400">Le client n'a pas encore laissé d'avis.</p>
            @endif
        </div>
        @endif
    </div>
</div>

@endsection
