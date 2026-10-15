@extends('layouts.app')

@section('title', 'Paiement annulé — RETISS')

@section('styles')
<style>
.payment-page {
    min-height: 100vh;
    background: linear-gradient(160deg, #fef2f2 0%, #f8fafc 50%, #fff7ed 100%);
    padding-top: 72px;
}
.cancel-hero {
    background: linear-gradient(135deg, #7f1d1d 0%, #1c1917 60%, #3f3f46 100%);
    padding: 4rem 0 7rem;
    position: relative; overflow: hidden;
}
.cancel-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(ellipse at 40% 40%, rgba(239,68,68,0.2) 0%, transparent 60%);
    pointer-events: none;
}
.hero-grid {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
    background-size: 40px 40px;
}
.content-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.08);
    padding: 2.5rem;
    margin-top: -4rem;
    position: relative; z-index: 10;
}
.cancel-icon {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 9999px;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 1.25rem;
    box-shadow: 0 8px 30px rgba(239,68,68,0.4);
    animation: pop .4s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes pop {
    from { transform: scale(0); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}
.btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    background: #0d9488; color: #fff; font-weight: 700; font-size: 14px;
    padding: 12px 28px; border-radius: 12px; text-decoration: none;
    transition: background .2s, transform .2s;
}
.btn-primary:hover { background: #0f766e; transform: translateY(-1px); }
.btn-secondary {
    display: inline-flex; align-items: center; gap: 8px;
    background: #f8fafc; border: 1px solid #e2e8f0; color: #475569;
    font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 12px;
    text-decoration: none; transition: background .2s;
}
.btn-secondary:hover { background: #f1f5f9; }
</style>
@endsection

@section('content')
<div class="payment-page">

    {{-- Hero --}}
    <div class="cancel-hero">
        <div class="hero-grid"></div>
        <div class="max-w-2xl mx-auto px-4 text-center relative z-10">
            <div class="cancel-icon">✗</div>
            <h1 class="text-white font-bold text-3xl mb-2">Paiement annulé</h1>
            <p class="text-white/60 text-sm">Vous avez annulé le paiement. Votre panier a été conservé.</p>
        </div>
    </div>

    {{-- Main card --}}
    <div class="max-w-2xl mx-auto px-4 sm:px-6 pb-16">
        <div class="content-card">

            {{-- Notice --}}
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex gap-3">
                <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 flex-shrink-0"></i>
                <div>
                    <p class="text-amber-800 font-semibold text-sm">Paiement non effectué</p>
                    <p class="text-amber-600 text-xs mt-0.5">
                        Votre paiement n'a pas été complété. Aucun montant n'a été débité.
                        Vous pouvez retourner à votre panier et réessayer à tout moment.
                    </p>
                </div>
            </div>

            <p class="text-slate-600 text-sm leading-relaxed mb-6">
                La commande <strong class="text-slate-900">{{ $commande->numero }}</strong> a été
                <span class="text-red-600 font-semibold">annulée</span> et les articles ont été remis en vente.
                Vous pouvez effectuer un nouvel achat à tout moment.
            </p>

            {{-- Actions --}}
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('panier.index') }}" class="btn-primary">
                    <i class="fas fa-shopping-cart"></i> Retourner au panier
                </a>
                <a href="{{ route('articles.index') }}" class="btn-secondary">
                    <i class="fas fa-shopping-bag"></i> Parcourir les articles
                </a>
                <a href="{{ route('commandes.index') }}" class="btn-secondary">
                    <i class="fas fa-box"></i> Mes commandes
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
