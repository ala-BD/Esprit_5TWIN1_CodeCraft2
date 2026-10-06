@extends('layouts.app')

@section('title', 'Modifier la commande ' . $commande->numero . ' — RETISS')

@section('styles')
<style>
.edit-commande-page {
    min-height: 100vh;
    background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 50%, #f0f4ff 100%);
    padding-top: 72px;
}
.hero-edit {
    background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
    position: relative;
    overflow: hidden;
    padding: 2.5rem 0 5rem;
}
.hero-edit::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(ellipse at 80% 50%, rgba(13,148,136,0.25) 0%, transparent 60%);
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
.content-wrap {
    margin-top: -3.5rem;
    position: relative;
    z-index: 10;
}
.radio-card {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    border: 2px solid #e2e8f0;
}
.radio-card:hover {
    border-color: #0d9488;
    background-color: #f0fdf9;
}
.radio-card.active {
    border-color: #0d9488;
    background-color: #f0fdfa;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.12);
}
</style>
@endsection

@section('content')
<div class="edit-commande-page">

    {{-- HERO --}}
    <div class="hero-edit">
        <div class="hero-grid"></div>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('commandes.index') }}" class="text-white/50 hover:text-white/80 text-sm" style="text-decoration:none">
                    <i class="fas fa-box"></i> Mes commandes
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <a href="{{ route('commandes.show', $commande) }}" class="text-white/50 hover:text-white/80 text-sm" style="text-decoration:none">
                    {{ $commande->numero }}
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-medium">Modifier</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-extrabold text-white tracking-tight">
                        Modifier la commande {{ $commande->numero }}
                    </h1>
                    <p class="text-white/70 text-sm mt-1">
                        Vous pouvez ajuster l'adresse de livraison, le mode de paiement ou les quantités tant que la commande est en attente.
                    </p>
                </div>

                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-400/20 border border-amber-400/30 text-amber-200 text-xs font-bold">
                    <i class="fas fa-clock"></i> Statut : En attente
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN FORM --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 content-wrap pb-20">

        {{-- Errors --}}
        @if($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm">
                <div class="font-bold flex items-center gap-2 mb-1">
                    <i class="fas fa-circle-exclamation text-red-500"></i> Veuillez corriger les erreurs suivantes :
                </div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('commandes.update', $commande) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

                {{-- LEFT COLUMN : Quantities, Address, Payment --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Quantities of Order Lines --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-display font-bold text-slate-800 text-base mb-4 flex items-center gap-2">
                            <i class="fas fa-boxes text-teal-600"></i> Articles & Quantités
                        </h2>

                        <div class="space-y-4">
                            @foreach($commande->lignes as $index => $ligne)
                                @php $article = $ligne->article; @endphp
                                <input type="hidden" name="lignes[{{ $index }}][id]" value="{{ $ligne->id }}">

                                <div class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @if($article && $article->getFirstImageUrl())
                                            <img src="{{ $article->getFirstImageUrl() }}"
                                                 alt="{{ $article->titre }}"
                                                 class="w-14 h-14 object-cover rounded-xl shadow-sm border border-slate-200 flex-shrink-0">
                                        @else
                                            <div class="w-14 h-14 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center font-bold text-lg flex-shrink-0">
                                                <i class="fas fa-tshirt"></i>
                                            </div>
                                        @endif

                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-800 text-sm truncate">{{ $article->titre ?? 'Article' }}</p>
                                            <p class="text-xs text-slate-400">
                                                Prix unitaire : {{ number_format($ligne->prix_unitaire - $ligne->remise, 2) }} DT
                                                @if($ligne->remise > 0)
                                                    <span class="text-emerald-600 font-semibold">(Remise 10% déduite)</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <label class="text-xs text-slate-500 font-medium">Quantité :</label>
                                        <input type="number"
                                               name="lignes[{{ $index }}][quantite]"
                                               min="1"
                                               max="{{ ($article ? $article->stock : 0) + $ligne->quantite }}"
                                               value="{{ old("lignes.{$index}.quantite", $ligne->quantite) }}"
                                               class="w-18 py-1.5 px-3 text-center text-sm font-bold border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 outline-none"
                                               required>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Address Selection --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="font-display font-bold text-slate-800 text-base flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-teal-600"></i> Adresse de livraison
                            </h2>
                            <a href="{{ route('adresses.create') }}" target="_blank"
                               class="text-xs font-bold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1.5"
                               style="text-decoration:none">
                                <i class="fas fa-plus"></i> Nouvelle adresse
                            </a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="edit-addresses">
                            @foreach($adresses as $adr)
                                @php
                                    $isSelected = old('adresse_id', $commande->adresse_id) == $adr->id;
                                @endphp
                                <label class="radio-card rounded-2xl p-4 flex items-start gap-3 {{ $isSelected ? 'active' : '' }}"
                                       onclick="selectEditAddress(this)">
                                    <input type="radio" name="adresse_id" value="{{ $adr->id }}"
                                           class="mt-1 text-teal-600 focus:ring-teal-500"
                                           {{ $isSelected ? 'checked' : '' }} required>
                                    <div class="min-w-0 flex-1">
                                        <span class="font-bold text-slate-800 text-xs uppercase tracking-wide block mb-1">
                                            {{ $adr->label ?: 'Adresse' }}
                                        </span>
                                        <p class="text-xs text-slate-600 font-medium leading-relaxed">
                                            {{ $adr->rue }} {{ $adr->numero }}
                                        </p>
                                        <p class="text-[11px] text-slate-400">
                                            {{ $adr->code_postal }} {{ $adr->ville }} ({{ $adr->gouvernorat }})
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Payment Method Selection --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-display font-bold text-slate-800 text-base mb-4 flex items-center gap-2">
                            <i class="fas fa-credit-card text-teal-600"></i> Mode de paiement
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" id="edit-payment">
                            @foreach($modes as $key => $label)
                                @php
                                    $isSel = old('mode_paiement', $commande->mode_paiement) === $key;
                                @endphp
                                <label class="radio-card rounded-2xl p-4 flex flex-col justify-between gap-2 {{ $isSel ? 'active' : '' }}"
                                       onclick="selectEditPayment(this)">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs text-slate-800">{{ $label }}</span>
                                        <input type="radio" name="mode_paiement" value="{{ $key }}"
                                               class="text-teal-600 focus:ring-teal-500"
                                               {{ $isSel ? 'checked' : '' }} required>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- RIGHT COLUMN : Summary & Submit --}}
                <div class="space-y-5">
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sticky top-24">
                        <h3 class="font-display font-bold text-slate-800 text-base mb-4 pb-3 border-b border-slate-100">
                            Enregistrer les modifications
                        </h3>

                        <div class="p-3.5 rounded-2xl bg-teal-50 border border-teal-100 text-xs text-teal-900 leading-relaxed mb-6">
                            <i class="fas fa-info-circle text-teal-600 mr-1"></i>
                            Les totaux seront automatiquement recalculés à la validation selon les nouvelles quantités.
                        </div>

                        <div class="space-y-3">
                            <button type="submit"
                                    class="w-full py-3.5 px-5 rounded-2xl font-bold text-sm text-white bg-teal-600 hover:bg-teal-700 shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-check"></i>
                                <span>Mettre à jour la commande</span>
                            </button>

                            <a href="{{ route('commandes.show', $commande) }}"
                               class="w-full py-2.5 px-4 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold text-center block transition-colors"
                               style="text-decoration:none">
                                Annuler et retourner au suivi
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</div>

<script>
function selectEditAddress(labelEl) {
    document.querySelectorAll('#edit-addresses .radio-card').forEach(el => el.classList.remove('active'));
    labelEl.classList.add('active');
    const radio = labelEl.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

function selectEditPayment(labelEl) {
    document.querySelectorAll('#edit-payment .radio-card').forEach(el => el.classList.remove('active'));
    labelEl.classList.add('active');
    const radio = labelEl.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}
</script>
@endsection
