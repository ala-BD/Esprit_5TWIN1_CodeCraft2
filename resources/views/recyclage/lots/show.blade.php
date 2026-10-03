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
                    {{ $lot->etapeTraitements->whereNotNull('date_fin')->count() }} / 6 étapes terminées
                </p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm">Actions disponibles</h3>

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
                  onsubmit="return confirm('Confirmer la suppression du lot {{ $lot->reference }} ?')">
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
                $toutesEtapes = \App\Models\EtapeTraitement::ORDRE;
                $etapesMap = $lot->etapeTraitements->keyBy('type');
            @endphp

            <div class="relative">
                {{-- Ligne verticale --}}
                <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-100 z-0"></div>

                <div class="space-y-4">
                    @foreach($toutesEtapes as $type => $ordre)
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

        {{-- Formulaire ajout étape --}}
        @if($prochaineEtape && $lot->statut !== 'CERTIFIE')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
                <i class="fas fa-plus-circle text-primary-DEFAULT"></i>
                Ajouter l'étape suivante :
                <span class="text-primary-DEFAULT">
                    {{ \App\Models\EtapeTraitement::LABELS[$prochaineEtape] }}
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
                               class="w-full px-4 py-3 rounded-xl border {{ $errors->has('date_debut') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                      focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT text-gray-700 text-sm">
                        @error('date_debut')
                            <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Date de fin</label>
                        <input type="datetime-local" name="date_fin"
                               value="{{ old('date_fin') }}"
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
                    Ajouter l'étape {{ \App\Models\EtapeTraitement::LABELS[$prochaineEtape] }}
                </button>
            </form>
        </div>
        @endif
    </div>
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
