@extends('layouts.app-auth')

@section('title', 'Finaliser ma commande — RETISS')

@section('styles')
<style>
.checkout-page {
    min-height: 100vh;
    background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 50%, #f0f4ff 100%);
    padding-top: 72px;
}
.hero-checkout {
    background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
    position: relative;
    overflow: hidden;
    padding: 2.5rem 0 5rem;
}
.hero-checkout::before {
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
.content-wrap {
    margin-top: -3.5rem;
    position: relative;
    z-index: 10;
}

/* Custom Radio Card */
.radio-card {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    border: 2px solid #e2e8f0;
}
.radio-card:hover {
    border-color: #0d9488;
    background-color: #f0fdf9;
    transform: translateY(-2px);
}
.radio-card.active {
    border-color: #0d9488;
    background-color: #f0fdfa;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.12);
}

.step-bubble {
    width: 28px;
    height: 28px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
}
</style>
@endsection

@section('content')
<div class="checkout-page">

    {{-- HERO --}}
    <div class="hero-checkout">
        <div class="hero-grid"></div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white/80 text-sm" style="text-decoration:none">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <a href="{{ route('articles.index') }}" class="text-white/50 hover:text-white/80 text-sm" style="text-decoration:none">
                    Marketplace
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-medium">Finalisation de commande</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 border border-teal-400/30 text-teal-300 text-xs font-semibold mb-2">
                        <i class="fas fa-shield-alt text-xs"></i> Paiement sécurisé & Économie circulaire
                    </div>
                    <h1 class="font-display text-3xl font-extrabold text-white tracking-tight">
                        Passer ma commande
                    </h1>
                </div>

                {{-- Buyer Role Badge & Remise Indicator --}}
                @if(auth()->user()->getsRemise())
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-emerald-400/20 border border-emerald-400/30 text-emerald-200 text-xs font-semibold backdrop-blur-md">
                        <i class="fas fa-percent text-emerald-300"></i>
                        <span>Avantage <strong>{{ auth()->user()->role }}</strong> : -10% sur les créations Atelier</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MAIN FORM WRAP --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 content-wrap pb-20">

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm">
                <div class="font-bold flex items-center gap-2 mb-1">
                    <i class="fas fa-circle-exclamation text-red-500"></i> Erreur lors de la validation
                </div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('commandes.store') }}" method="POST" id="checkout-form">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

                {{-- ==================================================== --}}
                {{-- LEFT COLUMN (2 COLS) : STEPS                         --}}
                {{-- ==================================================== --}}
                <div class="lg:col-span-2 space-y-8">

                    {{-- STEP 1: ARTICLES REVIEW --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-7">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                            <div class="flex items-center gap-3">
                                <span class="step-bubble bg-teal-600 text-white">1</span>
                                <div>
                                    <h2 class="font-display font-bold text-slate-900 text-base">Articles sélectionnés</h2>
                                    <p class="text-xs text-slate-400">Vérifiez vos articles et ajustez les quantités</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                                {{ count($items) }} article(s)
                            </span>
                        </div>

                        <div class="space-y-4">
                            @php
                                $subtotalCalculated = 0;
                                $remiseCalculated = 0;
                            @endphp

                            @foreach($items as $index => $item)
                                @php
                                    $article = $item['article'];
                                    $qty = $item['quantite'];
                                    $sellerIsAtelier = $article->user && $article->user->role === \App\Models\User::ROLE_ATELIER;
                                    $hasRemise = auth()->user()->getsRemise() && $sellerIsAtelier;
                                    $prixUnitaire = (float) $article->prix;
                                    $remiseUnit = $hasRemise ? round($prixUnitaire * 0.10, 2) : 0;
                                    $finalUnit = $prixUnitaire - $remiseUnit;
                                    $lineTotal = round($finalUnit * $qty, 2);

                                    $subtotalCalculated += ($prixUnitaire * $qty);
                                    $remiseCalculated += ($remiseUnit * $qty);
                                @endphp

                                <input type="hidden" name="articles[{{ $index }}][id]" value="{{ $article->id }}">

                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-all">
                                    {{-- Image & Details --}}
                                    <div class="flex items-center gap-4 min-w-0">
                                        @if($article->getFirstImageUrl())
                                            <img src="{{ $article->getFirstImageUrl() }}"
                                                 alt="{{ $article->titre }}"
                                                 class="w-16 h-16 object-cover rounded-xl shadow-sm border border-slate-200 flex-shrink-0">
                                        @else
                                            <div class="w-16 h-16 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center font-bold text-xl flex-shrink-0">
                                                <i class="fas fa-tshirt"></i>
                                            </div>
                                        @endif

                                        <div class="min-w-0">
                                            <h3 class="font-bold text-slate-800 text-sm truncate">{{ $article->titre }}</h3>
                                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                                <span class="inline-flex items-center text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">
                                                    {{ $article->categorie }}
                                                </span>
                                                <span class="text-xs text-slate-400">
                                                    Vendu par <strong class="text-slate-600">{{ $article->user->full_name ?: $article->user->name }}</strong>
                                                    ({{ $article->user->role }})
                                                </span>
                                            </div>

                                            @if($hasRemise)
                                                <div class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md">
                                                    <i class="fas fa-tag text-[9px]"></i> Remise partenaire Atelier (-10%)
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Quantity & Price --}}
                                    <div class="flex items-center justify-between sm:justify-end gap-6 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-200">
                                        {{-- Quantity input --}}
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs text-slate-500 font-medium">Qté :</label>
                                            <input type="number"
                                                   name="articles[{{ $index }}][quantite]"
                                                   min="1"
                                                   max="{{ $article->stock }}"
                                                   value="{{ $qty }}"
                                                   class="w-16 py-1.5 px-2.5 text-center text-sm font-bold border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 outline-none"
                                                   onchange="document.getElementById('checkout-form').submit();"
                                                   title="Modifier la quantité">
                                        </div>

                                        {{-- Price --}}
                                        <div class="text-right">
                                            <div class="text-sm font-extrabold text-teal-800 font-display">
                                                {{ number_format($lineTotal, 2) }} DT
                                            </div>
                                            <div class="text-[11px] text-slate-400">
                                                @if($hasRemise)
                                                    <span class="line-through text-slate-400 mr-1">{{ number_format($prixUnitaire, 2) }}</span>
                                                    <span class="text-emerald-600 font-bold">{{ number_format($finalUnit, 2) }} DT/u</span>
                                                @else
                                                    <span>{{ number_format($prixUnitaire, 2) }} DT/u</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- STEP 2: DELIVERY ADDRESS --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-7">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                            <div class="flex items-center gap-3">
                                <span class="step-bubble bg-teal-600 text-white">2</span>
                                <div>
                                    <h2 class="font-display font-bold text-slate-900 text-base">Adresse de livraison</h2>
                                    <p class="text-xs text-slate-400">Sélectionnez où vous souhaitez recevoir vos articles</p>
                                </div>
                            </div>
                            <a href="{{ route('adresses.create') }}" target="_blank"
                               class="text-xs font-bold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 transition-colors"
                               style="text-decoration:none">
                                <i class="fas fa-plus"></i> Nouvelle adresse
                            </a>
                        </div>

                        @if($adresses->isEmpty())
                            <div class="p-6 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-center">
                                <div class="w-12 h-12 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center mx-auto mb-3 text-lg">
                                    <i class="fas fa-map-pin"></i>
                                </div>
                                <h4 class="font-bold text-sm mb-1">Aucune adresse enregistrée</h4>
                                <p class="text-xs text-amber-700 max-w-md mx-auto mb-4">
                                    Pour poursuivre votre commande et planifier la livraison, veuillez enregistrer une adresse de livraison.
                                </p>
                                <a href="{{ route('adresses.create') }}"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-md transition-all"
                                   style="text-decoration:none">
                                    <i class="fas fa-plus"></i> Ajouter une adresse maintenant
                                </a>
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="addresses-container">
                                @foreach($adresses as $adr)
                                    @php
                                        $isSelected = old('adresse_id', $adresseDefaut?->id ?? $adresses->first()->id) == $adr->id;
                                    @endphp
                                    <label class="radio-card rounded-2xl p-4 flex items-start gap-3.5 {{ $isSelected ? 'active' : '' }}"
                                           onclick="selectAddress(this)">
                                        <input type="radio" name="adresse_id" value="{{ $adr->id }}"
                                               class="mt-1 text-teal-600 focus:ring-teal-500"
                                               {{ $isSelected ? 'checked' : '' }} required>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-bold text-slate-800 text-xs uppercase tracking-wide">
                                                    {{ $adr->label ?: 'Adresse' }}
                                                </span>
                                                @if($adr->par_defaut)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-700">
                                                        Par défaut
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                                {{ $adr->rue }} {{ $adr->numero }}
                                            </p>
                                            <p class="text-[11px] text-slate-400">
                                                {{ $adr->code_postal }} {{ $adr->ville }} · {{ $adr->gouvernorat }}
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- STEP 3: PAYMENT METHOD --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-7">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                            <div class="flex items-center gap-3">
                                <span class="step-bubble bg-teal-600 text-white">3</span>
                                <div>
                                    <h2 class="font-display font-bold text-slate-900 text-base">Mode de paiement</h2>
                                    <p class="text-xs text-slate-400">Choisissez votre moyen de règlement préféré</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="payment-methods-container">
                            {{-- Carte --}}
                            @php $modeVal = old('mode_paiement', 'CARTE'); @endphp
                            <label class="radio-card rounded-2xl p-4 flex flex-col justify-between gap-3 text-center {{ $modeVal === 'CARTE' ? 'active' : '' }}"
                                   onclick="selectPayment(this)">
                                <div class="flex items-center justify-between">
                                    <div class="w-8 h-8 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-sm">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <input type="radio" name="mode_paiement" value="CARTE" class="text-teal-600 focus:ring-teal-500" {{ $modeVal === 'CARTE' ? 'checked' : '' }} required>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-slate-800 text-xs">Carte bancaire</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Paiement en ligne sécurisé (ClicToPay / CMI)</p>
                                </div>
                            </label>

                            {{-- Virement --}}
                            <label class="radio-card rounded-2xl p-4 flex flex-col justify-between gap-3 text-center {{ $modeVal === 'VIREMENT' ? 'active' : '' }}"
                                   onclick="selectPayment(this)">
                                <div class="flex items-center justify-between">
                                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                                        <i class="fas fa-building-columns"></i>
                                    </div>
                                    <input type="radio" name="mode_paiement" value="VIREMENT" class="text-teal-600 focus:ring-teal-500" {{ $modeVal === 'VIREMENT' ? 'checked' : '' }}>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-slate-800 text-xs">Virement bancaire</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Envoi des coordonnées RIB après confirmation</p>
                                </div>
                            </label>

                            {{-- À la livraison --}}
                            <label class="radio-card rounded-2xl p-4 flex flex-col justify-between gap-3 text-center {{ $modeVal === 'A_LA_LIVRAISON' ? 'active' : '' }}"
                                   onclick="selectPayment(this)">
                                <div class="flex items-center justify-between">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                                        <i class="fas fa-hand-holding-dollar"></i>
                                    </div>
                                    <input type="radio" name="mode_paiement" value="A_LA_LIVRAISON" class="text-teal-600 focus:ring-teal-500" {{ $modeVal === 'A_LA_LIVRAISON' ? 'checked' : '' }}>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-slate-800 text-xs">À la livraison</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Règlement en espèces lors de la réception</p>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>

                {{-- ==================================================== --}}
                {{-- RIGHT COLUMN (1 COL) : SUMMARY & SUBMIT              --}}
                {{-- ==================================================== --}}
                <div class="space-y-6">

                    {{-- Order Summary Card (Sticky) --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sticky top-24">
                        <h3 class="font-display font-bold text-slate-900 text-base mb-4 pb-3 border-b border-slate-100">
                            Récapitulatif de la commande
                        </h3>

                        @php
                            $fraisLivraison = \App\Models\Commande::FRAIS_LIVRAISON;
                            $totalFinal = max(0, $subtotalCalculated - $remiseCalculated + $fraisLivraison);
                        @endphp

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Sous-total articles</span>
                                <span class="font-semibold text-slate-800">{{ number_format($subtotalCalculated, 2) }} DT</span>
                            </div>

                            @if($remiseCalculated > 0)
                                <div class="flex items-center justify-between text-emerald-600 font-semibold bg-emerald-50 p-2.5 rounded-xl border border-emerald-100">
                                    <span class="flex items-center gap-1.5 text-xs">
                                        <i class="fas fa-tag"></i> Remise automatique (10%)
                                    </span>
                                    <span>− {{ number_format($remiseCalculated, 2) }} DT</span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-slate-600">
                                <span>Frais de livraison standard</span>
                                <span class="font-semibold text-slate-800">{{ number_format($fraisLivraison, 2) }} DT</span>
                            </div>

                            <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                                <div>
                                    <span class="font-display font-black text-slate-900 text-base">Total à payer</span>
                                    <p class="text-[10px] text-slate-400">TVA & frais de service inclus</p>
                                </div>
                                <div class="text-right">
                                    <span class="font-display font-black text-2xl text-teal-700">
                                        {{ number_format($totalFinal, 2) }}
                                    </span>
                                    <span class="font-bold text-sm text-slate-500">DT</span>
                                </div>
                            </div>
                        </div>

                        {{-- Eco-impact card --}}
                        @php
                            $totalArticlesCount = collect($items)->sum('quantite');
                            $co2Kg = round($totalArticlesCount * 3.5, 1);
                            $eauL = $totalArticlesCount * 2700;
                        @endphp
                        <div class="mt-6 p-4 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 text-xs">
                            <div class="flex items-center gap-2 font-bold text-emerald-800 mb-2">
                                <i class="fas fa-leaf text-emerald-600"></i>
                                <span>Impact écologique de votre commande</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-center pt-1">
                                <div class="bg-white/80 rounded-xl p-2 border border-emerald-100">
                                    <div class="font-extrabold text-emerald-700 text-base">{{ $co2Kg }} kg</div>
                                    <div class="text-[10px] text-slate-500">CO₂ évité</div>
                                </div>
                                <div class="bg-white/80 rounded-xl p-2 border border-emerald-100">
                                    <div class="font-extrabold text-emerald-700 text-base">{{ number_format($eauL) }} L</div>
                                    <div class="text-[10px] text-slate-500">Eau préservée</div>
                                </div>
                            </div>
                        </div>

                        {{-- Own article error if present --}}
                        @php
                            $hasOwnArticle = collect($items)->contains(fn($it) => $it['article']->user_id === auth()->id());
                        @endphp
                        @if($hasOwnArticle)
                            <div class="mt-4 p-3 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold flex items-center gap-2">
                                <i class="fas fa-ban text-red-500"></i>
                                <span>Un atelier ne peut pas acheter son propre article. Veuillez le retirer pour continuer.</span>
                            </div>
                        @endif

                        {{-- CTA Button --}}
                        <div class="mt-6 space-y-3">
                            <button type="submit"
                                    class="w-full py-4 px-6 rounded-2xl font-display font-bold text-sm text-white bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 shadow-lg shadow-teal-700/25 transition-all duration-200 flex items-center justify-center gap-2 {{ ($adresses->isEmpty() || $hasOwnArticle) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ ($adresses->isEmpty() || $hasOwnArticle) ? 'disabled' : '' }}>
                                <i class="fas fa-lock"></i>
                                <span>Confirmer la commande</span>
                            </button>

                            <a href="{{ route('articles.index') }}"
                               class="w-full py-2.5 px-4 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold text-center block transition-colors"
                               style="text-decoration:none">
                                Continuer mes achats
                            </a>
                        </div>

                        {{-- Guarantees --}}
                        <div class="mt-6 pt-4 border-t border-slate-100 grid grid-cols-3 gap-2 text-center text-[10px] text-slate-500">
                            <div>
                                <i class="fas fa-shield-halved text-teal-600 text-sm block mb-1"></i>
                                <span>Garantie RETISS</span>
                            </div>
                            <div>
                                <i class="fas fa-truck-fast text-teal-600 text-sm block mb-1"></i>
                                <span>Suivi en direct</span>
                            </div>
                            <div>
                                <i class="fas fa-qrcode text-teal-600 text-sm block mb-1"></i>
                                <span>Facture & QR</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </form>

    </div>
</div>

<script>
function selectAddress(labelEl) {
    document.querySelectorAll('#addresses-container .radio-card').forEach(el => el.classList.remove('active'));
    labelEl.classList.add('active');
    const radio = labelEl.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

function selectPayment(labelEl) {
    document.querySelectorAll('#payment-methods-container .radio-card').forEach(el => el.classList.remove('active'));
    labelEl.classList.add('active');
    const radio = labelEl.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}
</script>
@endsection
