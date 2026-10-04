@extends('upcycling.layouts.upcycling')

@section('title', 'Projet #' . $projet->id)

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.projets.index') }}" class="hover:text-primary transition-colors">Projets</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Projet #{{ $projet->id }}</span>
@endsection

@php
    $badge       = $projet->statut_badge;
    $devisAttente = $projet->devis_en_attente;
    $devisAccepte = $projet->devis_accepte;
    $annule      = $projet->statut === 'ANNULE';
    $difficultes = ['FACILE' => 'bg-green-100 text-green-700', 'MOYEN' => 'bg-amber-100 text-amber-700', 'DIFFICILE' => 'bg-red-100 text-red-600'];
@endphp

@section('upcycling-content')

{{-- ===== En-tête ===== --}}
<div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <h1 class="font-display text-2xl font-bold text-gray-900">{{ $projet->produit_final ?? 'Choisissez une idée' }}</h1>
            <span class="px-3 py-1 rounded-lg text-xs font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
        </div>
        <p class="text-gray-500 text-sm">
            {{ ucfirst($projet->type_vetement) }} en {{ $projet->matiere }} · demandé le {{ $projet->created_at->format('d/m/Y') }}
        </p>
    </div>
</div>

{{-- ===== Suivi par étapes ===== --}}
@unless($annule)
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-5">
        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
            <i class="fas fa-stream text-primary"></i> Suivi du projet
        </h3>
        <span class="text-sm font-semibold text-primary">{{ $projet->progression }}%</span>
    </div>

    <div class="grid grid-cols-7 gap-1">
        @foreach(\App\Models\ProjetUpcycling::ETAPES as $code => $etape)
            @php
                $position = $loop->index;
                $faite    = $position < $projet->index_etape || $projet->statut === 'TERMINE';
                $courante = $position === $projet->index_etape && $projet->statut !== 'TERMINE';
            @endphp
            <div class="flex flex-col items-center text-center">
                <div class="relative w-full flex items-center justify-center">
                    @unless($loop->first)
                        <div class="absolute right-1/2 w-full h-0.5 {{ $faite || $courante ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                    @endunless
                    <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center shadow-sm
                        {{ $faite ? 'bg-green-500' : ($courante ? 'bg-blue-500 animate-pulse' : 'bg-gray-100') }}">
                        <i class="fas {{ $faite ? 'fa-check' : $etape['icon'] }} text-sm {{ $faite || $courante ? 'text-white' : 'text-gray-400' }}"></i>
                    </div>
                </div>
                <p class="mt-2 text-[11px] sm:text-xs font-medium {{ $faite ? 'text-green-700' : ($courante ? 'text-blue-700' : 'text-gray-400') }}">
                    {{ $etape['label'] }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- Action atelier : étape suivante --}}
    @if($estAtelierProjet && $projet->etape_suivante)
        <form method="POST" action="{{ route('upcycling.projets.avancer', $projet) }}" class="mt-6 pt-5 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            @csrf @method('PATCH')
            <p class="text-sm text-gray-600">
                Travaux en cours. Étape suivante :
                <strong>{{ \App\Models\ProjetUpcycling::ETAPES[$projet->etape_suivante]['label'] }}</strong>
            </p>
            <button type="submit" class="inline-flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors">
                <i class="fas fa-forward"></i> Valider l'étape « {{ \App\Models\ProjetUpcycling::ETAPES[$projet->etape_suivante]['label'] }} »
            </button>
        </form>
    @endif
</div>
@else
<div class="bg-gray-100 border border-gray-200 rounded-2xl p-5 mb-6 flex items-center gap-3 text-gray-600 text-sm">
    <i class="fas fa-ban"></i> Ce projet a été annulé.
</div>
@endunless

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ===== COLONNE GAUCHE ===== --}}
    <div class="space-y-5">

        {{-- Infos vêtement --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-tshirt text-primary"></i> Le vêtement</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><span class="text-gray-500">Type</span><span class="font-medium text-gray-800">{{ ucfirst($projet->type_vetement) }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Matière</span><span class="font-medium text-gray-800">{{ ucfirst($projet->matiere) }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">État</span><span class="font-medium text-gray-800">{{ \App\Models\ProjetUpcycling::ETATS[$projet->etat] ?? $projet->etat }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Budget max</span><span class="font-medium text-gray-800">{{ $projet->budget_max ? number_format($projet->budget_max, 0) . ' DT' : '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Client</span><span class="font-medium text-gray-800">{{ $projet->client->full_name }}</span></div>
                @if($projet->donVetement)
                    <div class="flex justify-between gap-4"><span class="text-gray-500">Don d'origine</span><span class="font-medium text-gray-800">#{{ $projet->donVetement->id }}</span></div>
                @endif
                @if($projet->date_debut)
                    <div class="flex justify-between gap-4"><span class="text-gray-500">Début des travaux</span><span class="font-medium text-gray-800">{{ $projet->date_debut->format('d/m/Y') }}</span></div>
                @endif
                @if($projet->date_fin)
                    <div class="flex justify-between gap-4"><span class="text-gray-500">Terminé le</span><span class="font-medium text-gray-800">{{ $projet->date_fin->format('d/m/Y') }}</span></div>
                @endif
            </div>
            <p class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-600 italic">« {{ $projet->description }} »</p>
        </div>

        {{-- Atelier --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-store text-primary"></i> Atelier</h3>
            @if($projet->atelier)
                <a href="{{ route('upcycling.ateliers.show', $projet->atelier) }}" class="flex items-center gap-3 group">
                    <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $projet->atelier->icone }} text-primary"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 group-hover:text-primary transition-colors">{{ $projet->atelier->nom }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $projet->atelier->specialite_label }} · {{ $projet->atelier->localisation }}
                            @if($projet->atelier->note_moyenne > 0) · <i class="fas fa-star text-amber-400"></i> {{ number_format($projet->atelier->note_moyenne, 1) }} @endif
                        </p>
                    </div>
                </a>
                @if($estProprietaire && $projet->statut === 'ATELIER_CHOISI')
                    <a href="{{ route('upcycling.projets.matching', $projet) }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-primary">
                        <i class="fas fa-exchange-alt"></i> Changer d'atelier
                    </a>
                @endif
            @elseif($estProprietaire && !$annule)
                <p class="text-sm text-gray-500 mb-4">
                    {{ $projet->produit_final ? 'Votre idée est choisie : trouvez l\'atelier qui la réalisera.' : 'Choisissez d\'abord une idée parmi les propositions de l\'IA.' }}
                </p>
                @if($projet->produit_final)
                    <a href="{{ route('upcycling.projets.matching', $projet) }}"
                       class="flex items-center justify-center gap-2 w-full bg-gradient-to-r from-primary-dark to-primary text-white font-semibold py-3 rounded-xl hover:shadow-lg transition-all text-sm">
                        <i class="fas fa-bullseye"></i> Trouver un atelier (matching)
                    </a>
                @endif
            @else
                <p class="text-sm text-gray-400">Aucun atelier assigné.</p>
            @endif
        </div>

        {{-- Actions client --}}
        @if($estProprietaire && ($projet->estModifiable() || $projet->estAnnulable() || in_array($projet->statut, ['DEMANDE', 'ANNULE'])))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm">Actions</h3>
            @if($projet->estModifiable())
                <a href="{{ route('upcycling.projets.edit', $projet) }}"
                   class="flex items-center gap-3 w-full bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold px-4 py-3 rounded-xl transition-all text-sm">
                    <i class="fas fa-pen"></i> Modifier la demande
                </a>
            @endif
            @if($projet->estAnnulable())
                <form method="POST" action="{{ route('upcycling.projets.annuler', $projet) }}" onsubmit="return confirm('Annuler ce projet ?')">
                    @csrf @method('PATCH')
                    <button class="flex items-center gap-3 w-full bg-gray-50 hover:bg-gray-100 text-gray-600 font-semibold px-4 py-3 rounded-xl transition-all text-sm">
                        <i class="fas fa-ban"></i> Annuler le projet
                    </button>
                </form>
            @endif
            @if(in_array($projet->statut, ['DEMANDE', 'ANNULE']))
                <form method="POST" action="{{ route('upcycling.projets.destroy', $projet) }}" onsubmit="return confirm('Supprimer définitivement cette demande ?')">
                    @csrf @method('DELETE')
                    <button class="flex items-center gap-3 w-full bg-red-50 hover:bg-red-100 text-red-600 font-semibold px-4 py-3 rounded-xl transition-all text-sm">
                        <i class="fas fa-trash-alt"></i> Supprimer
                    </button>
                </form>
            @endif
        </div>
        @endif
    </div>

    {{-- ===== COLONNE DROITE ===== --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Idées IA --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-amber-400"></i> Idées d'upcycling générées par l'IA
                    @if($projet->source_ia === 'CLAUDE')
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700">Claude</span>
                    @else
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500" title="Aucune clé API configurée : générateur local">Générateur local</span>
                    @endif
                </h3>
                @if($estProprietaire && $projet->estModifiable())
                    <form method="POST" action="{{ route('upcycling.projets.idees', $projet) }}"
                          onsubmit="this.querySelector('button').disabled = true; this.querySelector('span').textContent = 'Génération…';">
                        @csrf
                        <button class="inline-flex items-center gap-2 text-xs font-semibold text-violet-600 bg-violet-50 hover:bg-violet-100 px-3 py-2 rounded-lg transition-colors disabled:opacity-60">
                            <i class="fas fa-sync-alt"></i> <span>Autres idées</span>
                        </button>
                    </form>
                @endif
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                @foreach($projet->idee_generee_ia ?? [] as $index => $idee)
                    @php $choisie = $projet->produit_final === $idee['titre']; @endphp
                    <div class="rounded-2xl border-2 p-4 flex flex-col {{ $choisie ? 'border-primary bg-primary/5' : 'border-gray-100' }}">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-9 h-9 rounded-lg bg-white border border-gray-100 flex items-center justify-center">
                                <i class="fas {{ \App\Models\Atelier::ICONS[$idee['categorie']] ?? 'fa-cut' }} text-primary text-sm"></i>
                            </div>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $difficultes[$idee['difficulte']] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst(strtolower($idee['difficulte'])) }}
                            </span>
                        </div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $idee['titre'] }}</p>
                        <p class="text-xs text-gray-400 mb-2">{{ \App\Models\Atelier::SPECIALITES[$idee['categorie']] ?? $idee['categorie'] }} · ~{{ $idee['duree_heures'] }} h</p>
                        <p class="text-xs text-gray-600 leading-relaxed flex-1">{{ $idee['description'] }}</p>
                        @if(!empty($idee['materiaux']))
                            <div class="flex flex-wrap gap-1 mt-3">
                                @foreach($idee['materiaux'] as $materiau)
                                    <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $materiau }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($choisie)
                            <p class="mt-4 text-center text-xs font-semibold text-primary"><i class="fas fa-check-circle"></i> Idée retenue</p>
                        @elseif($estProprietaire && $projet->estModifiable())
                            <form method="POST" action="{{ route('upcycling.projets.idee', $projet) }}" class="mt-4">
                                @csrf @method('PATCH')
                                <input type="hidden" name="index" value="{{ $index }}">
                                <button class="w-full text-xs font-semibold py-2 rounded-lg border-2 border-primary text-primary hover:bg-primary hover:text-white transition-colors">
                                    Choisir cette idée
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Devis --}}
        @if($projet->atelier_id || $projet->devis->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-primary"></i> Devis
            </h3>

            {{-- Formulaire atelier --}}
            @if($estAtelierProjet && $projet->statut === 'ATELIER_CHOISI' && !$devisAttente)
                <form method="POST" action="{{ route('upcycling.devis.store', $projet) }}" class="bg-gray-50 rounded-xl p-5 mb-5 space-y-4" novalidate>
                    @csrf
                    <p class="text-sm font-semibold text-gray-700">
                        Proposer un devis
                        @if($projet->budget_max) <span class="font-normal text-gray-500">— budget du client : {{ number_format($projet->budget_max, 0) }} DT</span> @endif
                    </p>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Montant (DT) <span class="text-red-500">*</span></label>
                            <input type="number" name="montant" step="0.5" min="1" value="{{ old('montant') }}"
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
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Détail de la prestation</label>
                        <textarea name="message" rows="2" placeholder="Travaux prévus, fournitures incluses…"
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm resize-none">{{ old('message') }}</textarea>
                    </div>
                    <button class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors">
                        <i class="fas fa-paper-plane"></i> Envoyer le devis
                    </button>
                </form>
            @elseif($projet->statut === 'ATELIER_CHOISI' && !$devisAttente && $estProprietaire)
                <p class="text-sm text-gray-500 bg-gray-50 rounded-xl p-4 mb-5"><i class="fas fa-hourglass-half mr-1"></i> En attente du devis de l'atelier.</p>
            @endif

            @forelse($projet->devis as $devis)
                @php $badgeDevis = $devis->statut_badge; @endphp
                <div class="border border-gray-100 rounded-xl p-4 mb-3 last:mb-0 {{ $devis->statut === 'EN_ATTENTE' ? 'bg-yellow-50/50' : '' }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-gray-900 text-lg">{{ number_format($devis->montant, 2, ',', ' ') }} DT
                                <span class="text-sm font-normal text-gray-500">· {{ $devis->delai_jours }} jour(s)</span>
                            </p>
                            <p class="text-xs text-gray-400">Émis le {{ $devis->date_emission->format('d/m/Y') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $badgeDevis['class'] }}">{{ $badgeDevis['label'] }}</span>
                            @if($estProprietaire && $devis->statut === 'EN_ATTENTE')
                                <form method="POST" action="{{ route('upcycling.devis.accepter', $devis) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs font-semibold bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg transition-colors">
                                        <i class="fas fa-check"></i> Accepter
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('upcycling.devis.refuser', $devis) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-lg transition-colors">
                                        <i class="fas fa-times"></i> Refuser
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @if($devis->message)
                        <p class="text-sm text-gray-600 mt-2">{{ $devis->message }}</p>
                    @endif
                    @if($devis->statut === 'EN_ATTENTE' && $projet->budget_max && $devis->montant > $projet->budget_max)
                        <p class="text-xs text-amber-600 mt-2"><i class="fas fa-exclamation-triangle"></i> Dépasse le budget indiqué ({{ number_format($projet->budget_max, 0) }} DT).</p>
                    @endif
                </div>
            @empty
                @unless($estAtelierProjet || $estProprietaire)
                    <p class="text-sm text-gray-400">Aucun devis.</p>
                @endunless
            @endforelse
        </div>
        @endif

        {{-- Avis client --}}
        @if($projet->statut === 'TERMINE')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-star text-amber-400"></i> Avis du client</h3>

            @if($projet->note_client)
                <div class="flex items-center gap-1 mb-2">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star {{ $i <= $projet->note_client ? 'text-amber-400' : 'text-gray-200' }}"></i>
                    @endfor
                    <span class="ml-2 text-sm font-semibold text-gray-700">{{ $projet->note_client }}/5</span>
                </div>
                @if($projet->commentaire_client)
                    <p class="text-sm text-gray-600 italic">« {{ $projet->commentaire_client }} »</p>
                @endif
            @elseif($estProprietaire)
                <form method="POST" action="{{ route('upcycling.projets.noter', $projet) }}" class="space-y-4" novalidate>
                    @csrf @method('PATCH')
                    <div class="flex flex-row-reverse justify-end gap-1">
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" id="note-{{ $i }}" name="note_client" value="{{ $i }}" class="peer sr-only" @checked(old('note_client') == $i)>
                            <label for="note-{{ $i }}" class="cursor-pointer text-2xl text-gray-200 hover:text-amber-400 peer-hover:text-amber-400 peer-checked:text-amber-400 transition-colors">
                                <i class="fas fa-star"></i>
                            </label>
                        @endfor
                    </div>
                    @error('note_client')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                    <textarea name="commentaire_client" rows="2" placeholder="Votre avis sur le travail de l'atelier…"
                              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm resize-none">{{ old('commentaire_client') }}</textarea>
                    <button class="inline-flex items-center gap-2 bg-amber-400 hover:bg-amber-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors">
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
