@extends('layouts.app')

@section('title', 'Paiement confirmé — ' . $commande->numero . ' — RETISS')

@section('styles')
<style>
.payment-page {
    min-height: 100vh;
    background: linear-gradient(160deg, #ecfdf5 0%, #f8fafc 50%, #f0fdfa 100%);
    padding-top: 72px;
}
.success-hero {
    background: linear-gradient(135deg, #111a30 0%, #1B4332 60%, #0d9488 100%);
    padding: 4rem 0 7rem;
    position: relative; overflow: hidden;
}
.success-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(ellipse at 60% 40%, rgba(16,185,129,0.25) 0%, transparent 60%);
    pointer-events: none;
}
.hero-grid {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
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
.success-icon {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 9999px;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 1.25rem;
    box-shadow: 0 8px 30px rgba(16,185,129,0.4);
    animation: pop .4s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes pop {
    from { transform: scale(0); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}
.order-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: #ecfdf5; color: #059669;
    border: 1px solid #a7f3d0; border-radius: 999px;
    padding: 6px 18px; font-weight: 700; font-size: 14px;
    letter-spacing: 0.5px;
}
.info-table { width: 100%; }
.info-table tr td { padding: 8px 0; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
.info-table tr:last-child td { border-bottom: none; }
.info-table .label { color: #64748b; }
.info-table .value { font-weight: 600; color: #1e293b; text-align: right; }
.steps { display: flex; gap: 0; margin: 2rem 0 0; }
.step { flex: 1; text-align: center; position: relative; }
.step::before {
    content: ''; position: absolute; top: 16px; left: 50%; right: -50%;
    height: 2px; background: #e2e8f0; z-index: 0;
}
.step:last-child::before { display: none; }
.step-dot {
    width: 34px; height: 34px; border-radius: 9999px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 8px; font-size: 14px; font-weight: 700;
    position: relative; z-index: 1;
}
.step-dot.done { background: #0d9488; color: #fff; }
.step-dot.current { background: #1a2744; color: #fff; box-shadow: 0 0 0 5px rgba(26,39,68,0.15); }
.step-dot.pending { background: #e2e8f0; color: #94a3b8; }
.step-label { font-size: 11px; font-weight: 600; color: #64748b; }
.step-label.active { color: #0d9488; }
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
    <div class="success-hero">
        <div class="hero-grid"></div>
        <div class="max-w-2xl mx-auto px-4 text-center relative z-10">
            <div class="success-icon">✅</div>
            <h1 class="text-white font-bold text-3xl mb-2">Merci, nous confirmons votre paiement</h1>
            <p class="text-white/60 text-sm">Votre commande a bien été enregistrée et nous finalisons la confirmation de votre règlement.</p>
            <div class="order-badge mt-4">
                <i class="fas fa-hashtag" style="font-size:11px"></i> {{ $commande->numero }}
            </div>
        </div>
    </div>

    {{-- Main card --}}
    <div class="max-w-2xl mx-auto px-4 sm:px-6 pb-16">
        <div class="content-card">

            {{-- Payment info notice --}}
            @if($commande->estPaye())
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-6 flex gap-3">
                    <i class="fas fa-check-circle text-emerald-600 mt-0.5 flex-shrink-0 text-lg"></i>
                    <div>
                        <p class="text-emerald-900 font-bold text-sm">Paiement validé avec succès !</p>
                        <p class="text-emerald-700 text-xs mt-0.5">
                            Votre commande a été confirmée par Stripe. Votre facture PDF vous a été envoyée par email et votre colis entre en préparation.
                        </p>
                    </div>
                </div>
            @else
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <p class="text-blue-800 font-semibold text-sm">Confirmation en cours</p>
                        <p class="text-blue-600 text-xs mt-0.5">
                            Votre paiement est en cours de vérification par Stripe. Vous recevrez un email de confirmation
                            dans quelques instants avec votre facture en pièce jointe.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Order details --}}
            <h2 class="font-bold text-slate-900 text-lg mb-4">Récapitulatif de la commande</h2>
            <table class="info-table mb-6">
                <tr>
                    <td class="label">Numéro</td>
                    <td class="value" style="color:#0d9488">{{ $commande->numero }}</td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td class="value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</td>
                </tr>
                <tr>
                    <td class="label">Montant total</td>
                    <td class="value" style="font-size:18px;color:#0d9488">{{ number_format($commande->montant_total, 2) }} €</td>
                </tr>
                <tr>
                    <td class="label">Mode de paiement</td>
                    <td class="value">
                        <i class="fab fa-cc-stripe text-indigo-500"></i> Stripe Secure
                    </td>
                </tr>
                @if($commande->date_livraison_estimee)
                <tr>
                    <td class="label">Livraison estimée</td>
                    <td class="value" style="color:#0d9488">
                        <i class="fas fa-truck"></i> {{ $commande->date_livraison_estimee->format('d/m/Y') }}
                    </td>
                </tr>
                @endif
                @if($commande->adresse_snapshot)
                <tr>
                    <td class="label">Adresse</td>
                    <td class="value" style="font-size:12px">{{ $commande->adresse_snapshot }}</td>
                </tr>
                @endif
            </table>

            {{-- Status steps --}}
            <div class="steps">
                <div class="step">
                    <div class="step-dot done"><i class="fas fa-check text-xs"></i></div>
                    <div class="step-label active">Commande<br>créée</div>
                </div>
                <div class="step">
                    <div class="step-dot {{ $commande->estPaye() ? 'done' : 'current' }}">
                        @if($commande->estPaye())
                            <i class="fas fa-check text-xs"></i>
                        @else
                            <i class="fas fa-spinner fa-spin text-xs"></i>
                        @endif
                    </div>
                    <div class="step-label {{ $commande->estPaye() ? 'active' : '' }}">Paiement<br>confirmé</div>
                </div>
                <div class="step">
                    <div class="step-dot pending">3</div>
                    <div class="step-label">En<br>préparation</div>
                </div>
                <div class="step">
                    <div class="step-dot pending"><i class="fas fa-truck text-xs"></i></div>
                    <div class="step-label">Expédiée</div>
                </div>
                <div class="step">
                    <div class="step-dot pending"><i class="fas fa-home text-xs"></i></div>
                    <div class="step-label">Livrée</div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap gap-3 mt-8 pt-6 border-t border-slate-100">
                <a href="{{ route('commandes.show', $commande) }}" class="btn-primary">
                    <i class="fas fa-box"></i> Suivre ma commande
                </a>
                <a href="{{ route('commandes.invoice', $commande) }}" class="btn-secondary" target="_blank">
                    <i class="fas fa-file-pdf text-red-500"></i> Télécharger la facture
                </a>
                <a href="{{ route('articles.index') }}" class="btn-secondary">
                    <i class="fas fa-shopping-bag"></i> Continuer mes achats
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
