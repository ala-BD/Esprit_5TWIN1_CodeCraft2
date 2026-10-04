@extends('layouts.app')

@section('title', 'Mes Adresses — RETISS')

@section('styles')
<style>
    .addresses-page {
        min-height: 100vh;
        background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 40%, #f0f4ff 100%);
        padding-top: 72px;
    }

    .address-hero {
        background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
        position: relative;
        overflow: hidden;
        padding: 3rem 0 5rem;
    }
    .address-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(ellipse at 80% 50%, rgba(13,148,136,0.25) 0%, transparent 60%),
            radial-gradient(ellipse at 20% 80%, rgba(74,222,128,0.1) 0%, transparent 50%);
        pointer-events: none;
    }
    .address-hero-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 40px 40px;
        pointer-events: none;
    }

    .address-card-wrap {
        margin-top: -3.5rem;
        position: relative;
        z-index: 10;
    }

    .addr-card {
        background: #fff;
        border-radius: 1.5rem;
        border: 1.5px solid #e2e8f0;
        transition: all 0.25s ease;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .addr-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }
    .addr-card.is-default {
        border-color: #0d9488;
        background: linear-gradient(180deg, #f0fdf9 0%, #ffffff 40%);
        box-shadow: 0 10px 30px rgba(13, 148, 136, 0.1);
    }

    .addr-icon {
        width: 46px;
        height: 46px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: #fff;
        box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        flex-shrink: 0;
    }

    .badge-default {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        background: #ccfbf1;
        color: #0f766e;
        border: 1px solid #99f6e4;
        letter-spacing: 0.02em;
    }

    .status-dot-green {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #14b8a6;
    }

    /* Modal */
    .profile-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,0.6);
        z-index: 999;
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
    }
    .profile-modal-overlay.open { display: flex; animation: fadeIn 0.2s ease-out; }
    .profile-modal {
        background: #fff;
        border-radius: 1.5rem;
        width: 100%;
        max-width: 440px;
        margin: 1rem;
        box-shadow: 0 24px 64px rgba(15,23,42,0.2);
        overflow: hidden;
        animation: slideUp 0.3s ease-out;
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Toast */
    .toast {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: linear-gradient(135deg, #0d9488, #4ade80);
        color: #fff;
        padding: 1rem 1.5rem;
        border-radius: 1rem;
        font-weight: 600;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 8px 32px rgba(13,148,136,0.4);
        z-index: 9999;
        animation: toastIn 0.4s ease-out forwards;
    }
    @keyframes toastIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .toast.hiding { animation: toastOut 0.4s ease-in forwards; }
    @keyframes toastOut {
        from { opacity: 1; transform: translateY(0); }
        to   { opacity: 0; transform: translateY(10px); }
    }
</style>
@endsection

@section('content')
<div class="addresses-page">

    {{-- HERO BANNER --}}
    <div class="address-hero">
        <div class="address-hero-grid"></div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <a href="{{ route('profile.edit') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    Profil
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-medium">Mes adresses</span>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl sm:text-4xl font-extrabold text-white mb-2">
                        Carnet d'adresses
                    </h1>
                    <p class="text-white/70 text-sm max-w-xl">
                        Gérez vos lieux de collecte, points de livraison et ateliers. Définissez une adresse par défaut pour simplifier vos futures démarches.
                    </p>
                </div>

                <a href="{{ route('adresses.create') }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-bold text-sm text-white shadow-lg transition-all duration-200"
                   style="background: linear-gradient(135deg, #0d9488, #2DD4BF)"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 25px rgba(13,148,136,0.45)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.1)'">
                    <i class="fas fa-plus"></i>
                    <span>Ajouter une adresse</span>
                </a>
            </div>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 address-card-wrap pb-16">

        {{-- Toast success --}}
        @if(session('success'))
            <div id="flash-success" class="toast">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Stats bar --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 mb-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-base">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider">Adresses enregistrées</p>
                        <p class="text-lg font-extrabold text-slate-800">{{ $adresses->count() }}</p>
                    </div>
                </div>

                <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-base">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider">Adresse par défaut</p>
                        <p class="text-sm font-bold text-slate-800 truncate max-w-[200px]">
                            {{ $adresses->firstWhere('par_defaut', true)?->libelle ?: ($adresses->firstWhere('par_defaut', true)?->ville ?? 'Non définie') }}
                        </p>
                    </div>
                </div>
            </div>

            <a href="{{ route('adresses.create') }}" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1.5 transition-colors">
                <i class="fas fa-plus-circle"></i>
                Nouvelle adresse
            </a>
        </div>

        @if($adresses->isEmpty())
            {{-- Empty State --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-12 text-center max-w-lg mx-auto">
                <div class="w-20 h-20 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-3xl mx-auto mb-5">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3 class="font-display font-bold text-slate-800 text-xl mb-2">Aucune adresse enregistrée</h3>
                <p class="text-slate-500 text-sm mb-6 leading-relaxed">
                    Vous n'avez pas encore configuré d'adresses. Enregistrez votre domicile, lieu de travail ou atelier pour faciliter la collecte et livraison de vos textiles.
                </p>
                <a href="{{ route('adresses.create') }}"
                   class="btn-primary inline-flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Créer ma première adresse
                </a>
            </div>
        @else
            {{-- Grid of address cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($adresses as $adresse)
                    @php
                        $libelleLower = strtolower($adresse->libelle ?? '');
                        if (str_contains($libelleLower, 'maison') || str_contains($libelleLower, 'domicile')) {
                            $icon = 'fa-home';
                            $iconColor = 'linear-gradient(135deg, #0d9488, #4ade80)';
                        } elseif (str_contains($libelleLower, 'travail') || str_contains($libelleLower, 'bureau')) {
                            $icon = 'fa-briefcase';
                            $iconColor = 'linear-gradient(135deg, #1d4ed8, #60a5fa)';
                        } elseif (str_contains($libelleLower, 'atelier')) {
                            $icon = 'fa-cut';
                            $iconColor = 'linear-gradient(135deg, #7c3aed, #c4b5fd)';
                        } elseif (str_contains($libelleLower, 'dépôt') || str_contains($libelleLower, 'depot') || str_contains($libelleLower, 'entrepot')) {
                            $icon = 'fa-warehouse';
                            $iconColor = 'linear-gradient(135deg, #b45309, #fbbf24)';
                        } else {
                            $icon = 'fa-map-pin';
                            $iconColor = 'linear-gradient(135deg, #0f766e, #2DD4BF)';
                        }
                    @endphp

                    <div class="addr-card p-6 {{ $adresse->par_defaut ? 'is-default' : '' }}">
                        {{-- Top header --}}
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="addr-icon" style="background: {{ $iconColor }}">
                                    <i class="fas {{ $icon }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-slate-800 text-base truncate">
                                        {{ $adresse->libelle ?: 'Adresse' }}
                                    </h4>
                                    <p class="text-xs text-slate-400 font-medium">
                                        {{ $adresse->ville }}
                                    </p>
                                </div>
                            </div>

                            @if($adresse->par_defaut)
                                <span class="badge-default flex-shrink-0">
                                    <span class="status-dot-green"></span>
                                    Par défaut
                                </span>
                            @endif
                        </div>

                        {{-- Address details --}}
                        <div class="space-y-2 mb-6 flex-1 text-sm text-slate-600">
                            <div class="flex items-start gap-2.5">
                                <i class="fas fa-road text-slate-400 text-xs mt-1 flex-shrink-0"></i>
                                <span class="font-medium text-slate-700 leading-snug">{{ $adresse->rue }}</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="fas fa-city text-slate-400 text-xs flex-shrink-0"></i>
                                <span>{{ $adresse->code_postal }} {{ $adresse->ville }}</span>
                            </div>

                            @if($adresse->latitude && $adresse->longitude)
                                <div class="flex items-center gap-2 text-xs text-slate-400 pt-1">
                                    <i class="fas fa-location-arrow text-teal-500"></i>
                                    <span>GPS: {{ number_format($adresse->latitude, 4) }}, {{ number_format($adresse->longitude, 4) }}</span>
                                    <a href="https://www.google.com/maps?q={{ $adresse->latitude }},{{ $adresse->longitude }}"
                                       target="_blank" rel="noopener noreferrer"
                                       class="text-teal-600 hover:text-teal-700 ml-auto"
                                       title="Ouvrir dans Google Maps">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Footer actions --}}
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-2 mt-auto">
                            <div class="flex items-center gap-1">
                                <a href="{{ route('adresses.edit', $adresse) }}"
                                   class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-50 hover:bg-slate-100 transition-colors flex items-center gap-1.5"
                                   title="Modifier cette adresse">
                                    <i class="fas fa-pen text-slate-400"></i> Modifier
                                </a>

                                <button type="button"
                                        onclick="openDeleteModal('{{ route('adresses.destroy', $adresse) }}', '{{ addslashes($adresse->libelle ?: $adresse->rue) }}')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors flex items-center gap-1.5"
                                        title="Supprimer cette adresse">
                                    <i class="fas fa-trash-alt text-red-400"></i> Supprimer
                                </button>
                            </div>

                            @if(!$adresse->par_defaut)
                                <form method="POST" action="{{ route('adresses.defaut', $adresse) }}" class="inline">
                                    @csrf
                                    @method('patch')
                                    <button type="submit"
                                            class="text-xs font-semibold text-teal-600 hover:text-teal-700 hover:underline flex items-center gap-1"
                                            title="Définir comme adresse principale">
                                        <i class="far fa-star"></i> Par défaut
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

{{-- DELETE CONFIRMATION MODAL --}}
<div id="delete-modal" class="profile-modal-overlay" onclick="closeDeleteModalOutside(event)">
    <div class="profile-modal">
        <div class="p-6 pb-0">
            <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-trash-alt text-red-500 text-xl"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-lg mb-1">Supprimer cette adresse ?</h3>
            <p class="text-slate-500 text-sm leading-relaxed">
                Êtes-vous sûr de vouloir supprimer l'adresse « <strong id="delete-address-name" class="text-slate-800"></strong> » ? Cette action est irréversible.
            </p>
        </div>

        <form id="delete-form" method="POST" action="" class="p-6 pt-5">
            @csrf
            @method('delete')

            <div class="flex gap-3">
                <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors">
                    Annuler
                </button>
                <button type="submit" class="btn-danger flex-1 justify-center">
                    <i class="fas fa-trash-alt"></i>
                    Supprimer
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openDeleteModal(actionUrl, addressName) {
        document.getElementById('delete-form').action = actionUrl;
        document.getElementById('delete-address-name').textContent = addressName;
        document.getElementById('delete-modal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.remove('open');
        document.body.style.overflow = '';
    }

    function closeDeleteModalOutside(e) {
        if (e.target === document.getElementById('delete-modal')) {
            closeDeleteModal();
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });

    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('flash-success');
        if (toast) {
            setTimeout(function() {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 400);
            }, 3500);
        }
    });
</script>
@endsection
