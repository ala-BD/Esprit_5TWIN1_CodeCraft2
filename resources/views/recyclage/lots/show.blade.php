@extends('recyclage.layouts.recyclage')

@section('title', 'Lot ' . $lot->reference)

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('recyclage.lots.index') }}" class="hover:text-primary-DEFAULT transition-colors">Lots</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">{{ $lot->reference }}</span>
@endsection

@section('recyclage-content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ===== COLONNE GAUCHE : Infos lot ===== --}}
    <div class="lg:col-span-1 space-y-5">

        {{-- Card lot --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start justify-between mb-5">
                <div class="w-12 h-12 rounded-2xl bg-primary-DEFAULT/10 flex items-center justify-center">
                    <i class="fas fa-recycle text-primary-DEFAULT text-xl"></i>
                </div>
                @php $badge = $lot->statut_badge; @endphp
                <span class="px-3 py-1 rounded-lg text-xs font-semibold {{ $badge['class'] }}">
                    {{ $badge['label'] }}
                </span>
            </div>

            <h2 class="font-display text-xl font-bold text-gray-900 mb-1">{{ $lot->reference }}</h2>
            <p class="text-gray-500 text-sm mb-5">Enregistré le {{ $lot->created_at->format('d/m/Y à H:i') }}</p>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Poids</span>
                    <span class="font-semibold text-gray-900">{{ $lot->poids_kg }} kg</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Composition</span>
                    <span class="font-medium text-gray-700 text-right max-w-[150px]">{{ $lot->composition }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Origine</span>
                    <span class="font-medium text-gray-700 text-right max-w-[150px]">{{ $lot->origine }}</span>
                </div>
                <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                    <span class="text-gray-500">Filière IA</span>
                    @php $filiere = $lot->filiere_ia_badge; @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold {{ $filiere['class'] }}">
                        <i class="fas {{ $filiere['icon'] }} text-xs"></i>
                        {{ $filiere['label'] }}
                    </span>
                </div>
            </div>

            {{-- Progression --}}
            <div class="mt-5 pt-5 border-t border-gray-100">
                <div class="flex justify-between text-xs text-gray-500 mb-2">
                    <span>Progression traitement</span>
                    <span class="font-semibold">{{ $lot->progression }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-gradient-to-r from-primary-DEFAULT to-secondary-dark h-2 rounded-full transition-all duration-500"
                         style="width: {{ $lot->progression }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $lot->etapeTraitements->whereNotNull('date_fin')->whereIn('type', \App\Models\EtapeTraitement::CIRCUITS[$lot->filiere_finale] ?? [])->count() }}
                    / {{ count(\App\Models\EtapeTraitement::CIRCUITS[$lot->filiere_finale] ?? []) }} étapes terminées
                    — circuit {{ \App\Models\LotTextile::libelleFiliere($lot->filiere_finale) }}
                </p>
            </div>
        </div>

        {{-- Card Décision IA (modèle entraîné + explication SHAP) --}}
        @php
            $ia      = $lot->explication_ia ?? [];
            $probas  = $lot->probabilites_ia ?? [];
            arsort($probas);
            $couleursFiliere = [
                'REVENTE' => 'bg-emerald-500', 'UPCYCLING' => 'bg-amber-500',
                'RECYCLAGE_FIBRE' => 'bg-blue-500', 'RECYCLAGE_ENERGIE' => 'bg-red-500',
            ];
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 text-sm flex items-center gap-2">
                    <i class="fas fa-brain text-indigo-500"></i>
                    Décision IA
                </h3>
                @if($lot->source_ia === 'MODELE')
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600">
                        Modèle v{{ $lot->version_modele_ia }}
                    </span>
                @else
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-gray-100 text-gray-500">Règles métier</span>
                @endif
            </div>

            {{-- Probabilités par filière --}}
            @if($probas)
                <div class="space-y-2 mb-4">
                    @foreach($probas as $f => $p)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="{{ $f === $lot->filiere_recommandee_ia ? 'font-semibold text-gray-900' : 'text-gray-500' }}">
                                    {{ \App\Models\LotTextile::libelleFiliere($f) }}
                                </span>
                                <span class="font-mono {{ $f === $lot->filiere_recommandee_ia ? 'font-semibold text-gray-900' : 'text-gray-400' }}">{{ round($p * 100) }} %</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="{{ $couleursFiliere[$f] ?? 'bg-gray-400' }} h-1.5 rounded-full" style="width: {{ round($p * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Pourquoi ? (contributions SHAP) --}}
            @if(!empty($ia['raisons']))
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Pourquoi cette filière ?</p>
                <ul class="space-y-1.5 mb-4">
                    @foreach($ia['raisons'] as $raison)
                        <li class="flex items-center justify-between gap-2 text-xs">
                            <span class="text-gray-700">{{ $raison['facteur'] }}</span>
                            <span class="font-mono font-semibold whitespace-nowrap {{ $raison['sens'] === 'pour' ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ $raison['impact'] > 0 ? '+' : '' }}{{ $raison['impact'] }} %
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @foreach($ia['avertissements'] ?? [] as $avertissement)
                <p class="text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2 mb-2 flex gap-2">
                    <i class="fas fa-exclamation-triangle mt-0.5"></i> {{ $avertissement }}
                </p>
            @endforeach

            {{-- Validation humaine (apprentissage continu) --}}
            <div class="border-t border-gray-100 pt-4 mt-2">
                @if($lot->filiere_validee)
                    <p class="text-xs text-gray-500 mb-2">
                        <i class="fas fa-user-check text-teal-600"></i>
                        Filière retenue : <span class="font-semibold text-gray-900">{{ \App\Models\LotTextile::libelleFiliere($lot->filiere_validee) }}</span>
                        <span class="text-gray-400">({{ $lot->filiere_validee_le?->format('d/m/Y') }})</span>
                    </p>
                @endif
                <form method="POST" action="{{ route('recyclage.lots.filiere.valider', $lot) }}" class="flex gap-2">
                    @csrf
                    <select name="filiere" class="flex-1 text-xs rounded-lg border-gray-200 focus:border-indigo-400 focus:ring-indigo-200">
                        @foreach(\App\Models\LotTextile::FILIERES as $f)
                            <option value="{{ $f }}" @selected($f === $lot->filiere_finale)>{{ \App\Models\LotTextile::libelleFiliere($f) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                        Valider
                    </button>
                </form>
                @error('filiere') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                <form method="POST" action="{{ route('recyclage.lots.filiere.recalculer', $lot) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-600 hover:underline">
                        <i class="fas fa-sync-alt"></i> Recalculer la prédiction
                    </button>
                </form>
            </div>
        </div>

        {{-- Card Don rattaché --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-900 text-sm flex items-center gap-2 mb-4">
                <i class="fas fa-link text-primary-DEFAULT"></i>
                Don rattaché
            </h3>

            @if($lot->donVetement)
                {{-- Don déjà rattaché --}}
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 mb-3">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-tshirt text-emerald-600 text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-emerald-800 text-sm">Don n°{{ $lot->donVetement->id }}</p>
                            <p class="text-emerald-600 text-xs mt-0.5">
                                {{ $lot->donVetement->type }} — {{ $lot->donVetement->matiere }}
                            </p>
                            <p class="text-emerald-500 text-xs">
                                État : {{ $lot->donVetement->etat }}
                                @if($lot->donVetement->taille) | Taille : {{ $lot->donVetement->taille }} @endif
                            </p>
                        </div>
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-lg">RECYCLÉ</span>
                    </div>
                </div>

                @if($lot->statut !== 'CERTIFIE')
                <form method="POST" action="{{ route('recyclage.lots.detacher-don', $lot) }}"
                      onsubmit="return confirm('Détacher ce don ? Son statut reviendra à EN_TRI.')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="w-full text-xs text-red-500 hover:text-red-700 border border-red-200 hover:border-red-400
                                   hover:bg-red-50 px-3 py-2 rounded-xl transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-unlink"></i> Détacher ce don
                    </button>
                </form>
                @endif

            @else
                {{-- Pas de don rattaché --}}
                @if($lot->statut !== 'CERTIFIE' && $donsDisponibles->isNotEmpty())
                    <p class="text-gray-400 text-xs mb-3">
                        Rattachez un don en statut <strong>EN_TRI</strong> à ce lot.
                    </p>
                    <form method="POST" action="{{ route('recyclage.lots.rattacher-don', $lot) }}">
                        @csrf
                        @error('don_vetement_id')
                            <p class="text-red-500 text-xs mb-2 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                        <select name="don_vetement_id"
                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl
                                       focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT mb-3
                                       appearance-none bg-white text-gray-700">
                            <option value="">-- Choisir un don --</option>
                            @foreach($donsDisponibles as $don)
                                <option value="{{ $don->id }}">
                                    Don n°{{ $don->id }} — {{ $don->type }}, {{ $don->matiere }} ({{ $don->etat }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="w-full bg-primary-DEFAULT hover:bg-primary-dark text-white font-semibold
                                       text-sm px-4 py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-link"></i> Rattacher ce don
                        </button>
                    </form>
                @elseif($donsDisponibles->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-inbox text-gray-300 text-2xl mb-2 block"></i>
                        <p class="text-gray-400 text-xs">Aucun don EN_TRI disponible pour le moment.</p>
                    </div>
                @else
                    <p class="text-gray-400 text-xs text-center py-2">
                        Aucun don rattaché — lot certifié non modifiable.
                    </p>
                @endif
            @endif
        </div>

        {{-- Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm">Actions disponibles</h3>

            @if($lot->statut !== 'CERTIFIE')
            <a href="{{ route('recyclage.lots.edit', $lot) }}"
               class="flex items-center gap-3 w-full bg-amber-50 hover:bg-amber-100 text-amber-700
                      font-semibold px-4 py-3 rounded-xl transition-all duration-200 text-sm">
                <i class="fas fa-edit text-lg"></i>
                <div>
                    <p>Modifier ce lot</p>
                    <p class="text-amber-500 font-normal text-xs">Composition, origine, statut</p>
                </div>
            </a>
            @endif

            @if($lot->statut === 'TRAITE' && !$lot->passeportNumerique)
                <a href="{{ route('recyclage.passeport.show', $lot) }}"
                   class="flex items-center gap-3 w-full bg-purple-50 hover:bg-purple-100 text-purple-700
                          font-semibold px-4 py-3 rounded-xl transition-all duration-200 text-sm">
                    <i class="fas fa-qrcode text-lg"></i>
                    <div>
                        <p>Générer le passeport QR</p>
                        <p class="text-purple-500 font-normal text-xs">Certifier ce lot</p>
                    </div>
                </a>
            @endif

            @if($lot->passeportNumerique)
                <a href="{{ route('recyclage.passeport.show', $lot) }}"
                   class="flex items-center gap-3 w-full bg-purple-50 hover:bg-purple-100 text-purple-700
                          font-semibold px-4 py-3 rounded-xl transition-all duration-200 text-sm">
                    <i class="fas fa-certificate text-lg"></i>
                    <div>
                        <p>Voir le passeport</p>
                        <p class="text-purple-500 font-normal text-xs">{{ $lot->passeportNumerique->qr_code }}</p>
                    </div>
                </a>
                <a href="{{ route('recyclage.passeport.pdf', $lot) }}"
                   class="flex items-center gap-3 w-full bg-blue-50 hover:bg-blue-100 text-blue-700
                          font-semibold px-4 py-3 rounded-xl transition-all duration-200 text-sm">
                    <i class="fas fa-file-pdf text-lg"></i>
                    <div>
                        <p>Télécharger PDF</p>
                        <p class="text-blue-500 font-normal text-xs">Certificat officiel</p>
                    </div>
                </a>
            @endif

            @if($lot->statut !== 'CERTIFIE')
            <form method="POST" action="{{ route('recyclage.lots.destroy', $lot) }}"
                  data-confirm="Êtes-vous sûr de vouloir supprimer le lot {{ $lot->reference }} ? Cette action est irréversible."
                  data-confirm-title="Supprimer ce lot"
                  data-confirm-button="Oui, supprimer">
                @csrf @method('DELETE')
                <button type="submit"
                        class="flex items-center gap-3 w-full bg-red-50 hover:bg-red-100 text-red-600
                               font-semibold px-4 py-3 rounded-xl transition-all duration-200 text-sm">
                    <i class="fas fa-trash-alt"></i>
                    Supprimer ce lot
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- ===== COLONNE DROITE : Étapes ===== --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Timeline des étapes --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <i class="fas fa-stream text-primary-DEFAULT"></i>
                Suivi des étapes de traitement
            </h3>

            @php
                $toutesEtapes = $lot->circuit_etapes;
                $circuit = \App\Models\EtapeTraitement::CIRCUITS[$lot->filiere_finale] ?? [];
                $etapesMap = $lot->etapeTraitements->keyBy('type');
            @endphp
            <p class="text-xs text-gray-500 -mt-4 mb-5">
                Circuit adapté à la filière <strong>{{ \App\Models\LotTextile::libelleFiliere($lot->filiere_finale) }}</strong>
                ({{ count($circuit) }} étapes, dans l'ordre).
            </p>

            <div class="relative">
                {{-- Ligne verticale --}}
                <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-100 z-0"></div>

                <div class="space-y-4">
                    @foreach($toutesEtapes as $type)
                    @php
                        $etape    = $etapesMap->get($type);
                        $label    = \App\Models\EtapeTraitement::LABELS[$type];
                        $icone    = \App\Models\EtapeTraitement::ICONS[$type];
                        $terminee = $etape && $etape->date_fin;
                        $enCours  = $etape && !$etape->date_fin;
                    @endphp

                    <div class="relative flex gap-4 items-start z-10">
                        {{-- Indicateur --}}
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm
                            {{ $terminee ? 'bg-green-500' : ($enCours ? 'bg-blue-500 animate-pulse' : 'bg-gray-100') }}">
                            @if($terminee)
                                <i class="fas fa-check text-white text-sm"></i>
                            @elseif($enCours)
                                <i class="fas {{ $icone }} text-white text-sm"></i>
                            @else
                                <i class="fas {{ $icone }} text-gray-400 text-sm"></i>
                            @endif
                        </div>

                        {{-- Contenu --}}
                        <div class="flex-1 bg-gray-50 rounded-xl p-4 min-h-[56px]">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-sm {{ $terminee ? 'text-green-700' : ($enCours ? 'text-blue-700' : 'text-gray-400') }}">
                                        {{ $label }}
                                        @unless(in_array($type, $circuit))
                                            <span class="ml-1 text-[10px] font-medium px-1.5 py-0.5 rounded bg-gray-200 text-gray-500">hors circuit</span>
                                        @endunless
                                    </p>
                                    @if($etape)
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            Début : {{ $etape->date_debut->format('d/m/Y H:i') }}
                                            @if($terminee)
                                                — Fin : {{ $etape->date_fin->format('d/m/Y H:i') }}
                                                <span class="ml-1 text-green-600">({{ $etape->duree_heures }}h)</span>
                                            @endif
                                        </p>
                                        @if($etape->resultat)
                                            <p class="text-xs text-gray-600 mt-1 italic">{{ $etape->resultat }}</p>
                                        @endif
                                        @if($etape->poids_sortant_kg)
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Poids sortant : <strong>{{ $etape->poids_sortant_kg }} kg</strong>
                                            </p>
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-400 mt-0.5">Non commencée</p>
                                    @endif
                                </div>

                                {{-- Bouton terminer si en cours --}}
                                @if($enCours)
                                    <button onclick="document.getElementById('modal-terminer-{{ $etape->id }}').classList.remove('hidden')"
                                            class="flex-shrink-0 text-xs bg-blue-500 hover:bg-blue-600 text-white font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                        Terminer
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Étape en cours : il faut la terminer avant d'en ajouter une autre --}}
        @if($lot->etape_en_cours && $lot->statut !== 'CERTIFIE')
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
            <div class="text-sm text-blue-800">
                <p>Étape « <strong>{{ $lot->etape_en_cours->label }}</strong> » en cours.
                   Terminez-la avant de commencer l'étape suivante.</p>
                @error('type') <p class="text-red-600 mt-1">{{ $message }}</p> @enderror
                @error('date_fin') <p class="text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        @endif

        {{-- Formulaire ajout étape --}}
        @if($prochaineEtape && $lot->statut !== 'CERTIFIE')
        @php /** @var string $prochaineEtape */ @endphp
        @php $debutMin = $lot->debut_minimum_prochaine_etape; @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
                <i class="fas fa-plus-circle text-primary-DEFAULT"></i>
                Ajouter l'étape suivante :
                <span class="text-primary-DEFAULT">
                    {{ \App\Models\EtapeTraitement::LABELS[(string) $prochaineEtape] }}
                </span>
            </h3>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
                    <div>@foreach($errors->all() as $error)<p class="text-red-600 text-sm">{{ $error }}</p>@endforeach</div>
                </div>
            @endif

            <form method="POST" action="{{ route('recyclage.etapes.store', $lot) }}" class="space-y-4" novalidate>
                @csrf
                <input type="hidden" name="type" value="{{ $prochaineEtape }}">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Date de début <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="date_debut"
                               value="{{ old('date_debut', now()->format('Y-m-d\TH:i')) }}"
                               max="{{ now()->format('Y-m-d\TH:i') }}"
                               @if($debutMin) min="{{ $debutMin->format('Y-m-d\TH:i') }}" @endif
                               class="w-full px-4 py-3 rounded-xl border {{ $errors->has('date_debut') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                      focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT text-gray-700 text-sm">
                        @error('date_debut')
                            <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                        @enderror
                        @if($debutMin)
                            <p class="mt-1 text-xs text-gray-400">Au plus tôt le {{ $debutMin->format('d/m/Y à H:i') }} (fin de l'étape précédente).</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Date de fin</label>
                        <input type="datetime-local" name="date_fin"
                               value="{{ old('date_fin') }}"
                               max="{{ now()->format('Y-m-d\TH:i') }}"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200
                                      focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT text-gray-700 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Résultat / Observations</label>
                    <textarea name="resultat" rows="2" placeholder="Observations, remarques..."
                              class="w-full px-4 py-3 rounded-xl border border-gray-200
                                     focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT text-gray-700 text-sm resize-none">{{ old('resultat') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Poids sortant (kg)</label>
                    <input type="number" name="poids_sortant_kg" step="0.1" min="0"
                           value="{{ old('poids_sortant_kg') }}"
                           placeholder="kg après traitement"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT text-gray-700 text-sm">
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white
                               font-bold py-3 rounded-xl hover:shadow-lg hover:scale-[1.01]
                               transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-plus"></i>
                    Ajouter l'étape {{ \App\Models\EtapeTraitement::LABELS[(string) $prochaineEtape] }}
                </button>
            </form>
        </div>
        @endif
    </div>
</div>

{{-- ===== SECTION HISTORIQUE COMPLET ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
            <i class="fas fa-history text-primary-DEFAULT"></i>
            Historique complet du lot
        </h3>
        <span class="text-xs text-gray-400 bg-gray-100 px-2.5 py-1 rounded-lg">
            {{ $historiques->count() }} événement(s)
        </span>
    </div>

    @if($historiques->isEmpty())
        <div class="text-center py-8 text-gray-300">
            <i class="fas fa-history text-3xl mb-2 block"></i>
            <p class="text-sm text-gray-400">Aucun événement enregistré pour l'instant.</p>
        </div>
    @else
        <div class="relative">
            {{-- Ligne verticale --}}
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-100 z-0"></div>

            <div class="space-y-3">
                @foreach($historiques as $h)
                @php $badge = $h->badge; @endphp
                <div class="relative flex gap-4 items-start z-10">
                    {{-- Icône --}}
                    <div class="w-8 h-8 rounded-full {{ $badge['bg'] }} flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i class="fas {{ $h->icone }} {{ $badge['text'] }} text-xs"></i>
                    </div>

                    {{-- Contenu --}}
                    <div class="flex-1 bg-gray-50 rounded-xl px-4 py-3 min-w-0">
                        <div class="flex items-start justify-between gap-2 flex-wrap">
                            <p class="text-sm text-gray-800 font-medium leading-snug">{{ $h->description }}</p>
                            <span class="text-xs text-gray-400 whitespace-nowrap flex-shrink-0">
                                {{ $h->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 mt-1 flex-wrap">
                            @if($h->user)
                                <span class="text-xs text-gray-400">
                                    <i class="fas fa-user text-xs mr-1"></i>
                                    {{ $h->user->prenom ?? $h->user->name }}
                                </span>
                            @endif
                            <span class="text-xs text-gray-300">
                                {{ $h->created_at->format('d/m/Y à H:i') }}
                            </span>
                            @if($h->ancienne_valeur && $h->nouvelle_valeur)
                                <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded font-mono">
                                    {{ $h->ancienne_valeur }} → {{ $h->nouvelle_valeur }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Modals terminer étape --}}
@foreach($lot->etapeTraitements->whereNull('date_fin') as $etape)
<div id="modal-terminer-{{ $etape->id }}"
     class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="font-bold text-gray-900 text-lg mb-4">
            Terminer : {{ $etape->label }}
        </h3>
        <form method="POST" action="{{ route('recyclage.etapes.terminer', $etape) }}" novalidate>
            @csrf @method('PATCH')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date de fin <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="date_fin"
                           value="{{ now()->format('Y-m-d\TH:i') }}"
                           min="{{ $etape->date_debut->format('Y-m-d\TH:i') }}"
                           max="{{ now()->format('Y-m-d\TH:i') }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Résultat</label>
                    <textarea name="resultat" rows="2"
                              class="w-full px-4 py-3 rounded-xl border border-gray-200
                                     focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT text-sm resize-none"
                              placeholder="Résultat de l'étape..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Poids sortant (kg)</label>
                    <input type="number" name="poids_sortant_kg" step="0.1" min="0"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT text-sm">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-xl transition-colors">
                        <i class="fas fa-check mr-2"></i> Confirmer
                    </button>
                    <button type="button"
                            onclick="document.getElementById('modal-terminer-{{ $etape->id }}').classList.add('hidden')"
                            class="px-5 py-3 rounded-xl border-2 border-gray-200 text-gray-600 font-semibold hover:bg-gray-50">
                        Annuler
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach

@endsection
