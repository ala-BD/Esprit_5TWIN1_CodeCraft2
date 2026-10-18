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

/* ---- Stepper ---- */
.steps { display: flex; gap: 0; margin: 2rem 0 0; }
.step { flex: 1; text-align: center; position: relative; }
.step::before {
    content: ''; position: absolute; top: 17px; left: 50%; right: -50%;
    height: 3px; background: #e2e8f0; z-index: 0;
    transition: background 0.6s ease;
}
.step:last-child::before { display: none; }
.step.done-line::before {
    background: #0d9488;
}
.step-dot {
    width: 36px; height: 36px; border-radius: 9999px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 8px; font-size: 13px; font-weight: 700;
    position: relative; z-index: 1;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.step-dot.done {
    background: #0d9488; color: #fff;
    box-shadow: 0 4px 14px rgba(13,148,136,0.35);
}
.step-dot.processing {
    background: #0d9488; color: #fff;
    animation: prepPulse 1.6s infinite ease-in-out;
}
@keyframes prepPulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13,148,136,0.6); }
    70% { transform: scale(1.08); box-shadow: 0 0 0 10px rgba(13,148,136,0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13,148,136,0); }
}
.step-dot.current { background: #1a2744; color: #fff; box-shadow: 0 0 0 5px rgba(26,39,68,0.15); }
.step-dot.pending { background: #e2e8f0; color: #94a3b8; }
.step-label { font-size: 11px; font-weight: 600; color: #64748b; transition: color 0.3s; }
.step-label.active { color: #0d9488; font-weight: 700; }

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
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-6 flex gap-3" id="notice-card">
                    <i class="fas {{ $commande->statut === 'EXPEDIEE' ? 'fa-truck-fast text-teal-600' : 'fa-check-circle text-emerald-600' }} mt-0.5 flex-shrink-0 text-lg" id="notice-icon"></i>
                    <div>
                        <p class="text-emerald-900 font-bold text-sm" id="notice-title">
                            @if($commande->statut === 'EXPEDIEE')
                                Commande expédiée ! 🚚
                            @elseif($commande->statut === 'EN_PREPARATION')
                                Commande en préparation... 📦
                            @else
                                Paiement validé avec succès !
                            @endif
                        </p>
                        <p class="text-emerald-700 text-xs mt-0.5" id="notice-text">
                            @if($commande->statut === 'EXPEDIEE')
                                Votre commande a été préparée et remise à notre transporteur. Votre colis est actuellement en route vers votre adresse.
                            @elseif($commande->statut === 'EN_PREPARATION')
                                Votre paiement est validé. Notre atelier prépare actuellement votre colis avec soin.
                            @else
                                Votre commande a été confirmée par Stripe. Votre facture PDF vous a été envoyée par email et votre colis entre en préparation.
                            @endif
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
                    <td class="label">Statut actuel</td>
                    <td class="value">
                        <span id="statut-badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $commande->statut === 'EXPEDIEE' ? 'bg-teal-100 text-teal-800' : 'bg-emerald-100 text-emerald-800' }}">
                            <span class="w-2 h-2 rounded-full {{ $commande->statut === 'EXPEDIEE' ? 'bg-teal-500' : 'bg-emerald-500 animate-pulse' }}" id="statut-pulse"></span>
                            <span id="statut-label">{{ $commande->statutLabel() }}</span>
                        </span>
                    </td>
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

            @php
                $statutLevels = [
                    'EN_ATTENTE_PAIEMENT' => 0,
                    'EN_ATTENTE'          => 0,
                    'CONFIRMEE'           => 1,
                    'EN_PREPARATION'      => 2,
                    'EXPEDIEE'            => 3,
                    'LIVREE'              => 4,
                ];
                $currentLvl = $statutLevels[$commande->statut] ?? ($commande->estPaye() ? 2 : 1);
            @endphp

            {{-- Status steps --}}
            <div class="steps" id="checkout-stepper">
                {{-- Étape 1 : Commande créée --}}
                <div class="step {{ $currentLvl >= 1 ? 'done-line' : '' }}" id="step-1">
                    <div class="step-dot done"><i class="fas fa-check text-xs"></i></div>
                    <div class="step-label active">Commande<br>créée</div>
                </div>

                {{-- Étape 2 : Paiement confirmé --}}
                <div class="step {{ $currentLvl >= 2 ? 'done-line' : '' }}" id="step-2">
                    <div class="step-dot {{ $commande->estPaye() ? 'done' : 'current' }}" id="step-dot-2">
                        @if($commande->estPaye())
                            <i class="fas fa-check text-xs"></i>
                        @else
                            <i class="fas fa-spinner fa-spin text-xs"></i>
                        @endif
                    </div>
                    <div class="step-label {{ $commande->estPaye() ? 'active' : '' }}" id="step-label-2">Paiement<br>confirmé</div>
                </div>

                {{-- Étape 3 : En préparation --}}
                <div class="step {{ $currentLvl >= 3 ? 'done-line' : '' }}" id="step-3">
                    <div class="step-dot {{ $currentLvl > 2 ? 'done' : ($currentLvl === 2 ? 'processing' : 'pending') }}" id="step-dot-3">
                        @if($currentLvl > 2)
                            <i class="fas fa-check text-xs"></i>
                        @elseif($currentLvl === 2)
                            <i class="fas fa-box text-xs"></i>
                        @else
                            3
                        @endif
                    </div>
                    <div class="step-label {{ $currentLvl >= 2 ? 'active' : '' }}" id="step-label-3">En<br>préparation</div>
                </div>

                {{-- Étape 4 : Expédiée --}}
                <div class="step {{ $currentLvl >= 4 ? 'done-line' : '' }}" id="step-4">
                    <div class="step-dot {{ $currentLvl >= 3 ? 'done' : 'pending' }}" id="step-dot-4">
                        <i class="fas fa-truck text-xs"></i>
                    </div>
                    <div class="step-label {{ $currentLvl >= 3 ? 'active' : '' }}" id="step-label-4">Expédiée</div>
                </div>

                {{-- Étape 5 : Livrée --}}
                <div class="step" id="step-5">
                    <div class="step-dot {{ $currentLvl >= 4 ? 'done' : 'pending' }}" id="step-dot-5">
                        <i class="fas fa-home text-xs"></i>
                    </div>
                    <div class="step-label {{ $currentLvl >= 4 ? 'active' : '' }}">Livrée</div>
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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($commande->estPaye() && in_array($commande->statut, [\App\Models\Commande::STATUT_EN_PREPARATION, \App\Models\Commande::STATUT_CONFIRMEE]))
        const commandeId = {{ $commande->id }};
        const storageKey = 'retiss_expedie_notified_' + commandeId;

        // Si pas encore notifié dans cette session
        if (!sessionStorage.getItem(storageKey)) {
            // Délai de 2.2 secondes : le client voit l'étape "En préparation" active, puis transition vers "Expédiée" avec pop-up
            setTimeout(function () {
                fetch("{{ route('paiement.expedier', $commande) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        sessionStorage.setItem(storageKey, 'true');

                        // 1. Transition Stepper Étape 3 (En préparation -> Validée)
                        const step3 = document.getElementById('step-3');
                        const dot3 = document.getElementById('step-dot-3');
                        if (step3) step3.classList.add('done-line');
                        if (dot3) {
                            dot3.classList.remove('processing');
                            dot3.classList.add('done');
                            dot3.innerHTML = '<i class="fas fa-check text-xs"></i>';
                        }

                        // 2. Transition Stepper Étape 4 (Expédiée -> Validée/Active)
                        const dot4 = document.getElementById('step-dot-4');
                        const label4 = document.getElementById('step-label-4');
                        if (dot4) {
                            dot4.classList.remove('pending');
                            dot4.classList.add('done');
                            dot4.style.transform = 'scale(1.2)';
                            setTimeout(() => { dot4.style.transform = 'scale(1)'; }, 400);
                        }
                        if (label4) label4.classList.add('active');

                        // 3. Mise à jour du badge de statut et notice
                        const statutLabel = document.getElementById('statut-label');
                        if (statutLabel) statutLabel.textContent = 'Expédiée';

                        const statutBadge = document.getElementById('statut-badge');
                        if (statutBadge) {
                            statutBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800';
                        }
                        const statutPulse = document.getElementById('statut-pulse');
                        if (statutPulse) {
                            statutPulse.className = 'w-2 h-2 rounded-full bg-teal-500';
                        }

                        const noticeIcon = document.getElementById('notice-icon');
                        if (noticeIcon) {
                            noticeIcon.className = 'fas fa-truck-fast text-teal-600 mt-0.5 flex-shrink-0 text-lg';
                        }

                        const noticeTitle = document.getElementById('notice-title');
                        if (noticeTitle) noticeTitle.textContent = 'Commande expédiée avec succès ! 🚚';

                        const noticeText = document.getElementById('notice-text');
                        if (noticeText) noticeText.textContent = 'Votre commande a été préparée avec soin et remise au transporteur. Votre colis est actuellement en route vers votre adresse !';

                        // 4. Pop-up SweetAlert2
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '🎉 Commande Expédiée !',
                                html: `
                                    <div class="text-left text-slate-700 text-sm space-y-3 mt-2">
                                        <p class="font-medium text-slate-900">
                                            Votre commande <strong class="text-teal-600 font-bold">#{{ $commande->numero }}</strong> vient d'être préparée par notre atelier et a été <strong>remise au transporteur</strong> pour expédition immédiate.
                                        </p>
                                        <div class="p-3.5 bg-teal-50 rounded-2xl border border-teal-200 text-xs text-teal-900 flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center flex-shrink-0 text-lg shadow-sm">
                                                <i class="fas fa-truck-fast"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-teal-950">Statut de livraison : Expédiée 🚚</p>
                                                <p class="text-teal-700 mt-0.5">Votre colis est en cours d'acheminement vers votre adresse de livraison.</p>
                                            </div>
                                        </div>
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonText: '<i class="fas fa-route mr-1.5"></i> Suivre ma commande',
                                confirmButtonColor: '#0d9488',
                                showCancelButton: true,
                                cancelButtonText: 'Rester ici',
                                cancelButtonColor: '#64748b',
                                customClass: {
                                    popup: 'retiss-swal-popup',
                                    confirmButton: 'px-6 py-2.5 rounded-xl font-bold text-white',
                                    cancelButton: 'px-5 py-2.5 rounded-xl font-semibold'
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "{{ route('commandes.show', $commande) }}";
                                }
                            });
                        }
                    }
                })
                .catch(err => console.error('[Expedier Error]', err));
            }, 2200);
        }
    @endif
});
</script>
@endsection
