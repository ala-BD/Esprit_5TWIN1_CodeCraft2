@extends('layouts.app')
@section('title', 'Administration — RETISS')
@section('body-class', 'no-hero')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-navy-900 py-10">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
        <div>
            <p class="text-sm font-semibold text-red-400 uppercase tracking-widest mb-1">
                <i class="fas fa-shield-alt mr-1"></i> Panel Administrateur
            </p>
            <h1 class="font-display text-3xl font-bold text-white">
                Bonjour, <span class="text-red-400">{{ Auth::user()->prenom ?? Auth::user()->name }}</span> 🛡️
            </h1>
            <p class="text-slate-400 mt-1 text-sm">Vue globale de la plateforme RETISS.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-2 text-sm text-emerald-400 bg-emerald-400/10 px-4 py-2 rounded-xl border border-emerald-400/20">
                <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                Système actif
            </span>
        </div>
    </div>

    {{-- STATS GLOBALES --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-5 hover:bg-white/10 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-users text-blue-400"></i>
                </div>
                <span class="text-xs text-blue-400 font-semibold">Total</span>
            </div>
            <p class="text-2xl font-bold text-white">{{ $stats['total_users'] }}</p>
            <p class="text-slate-400 text-xs mt-0.5">Utilisateurs</p>
        </div>
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-5 hover:bg-white/10 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-tshirt text-teal-400"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-white">{{ $stats['total_articles'] }}</p>
            <p class="text-slate-400 text-xs mt-0.5">Articles publiés</p>
        </div>
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-5 hover:bg-white/10 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-shopping-bag text-amber-400"></i>
                </div>
                @if($stats['en_attente'] > 0)
                <span class="text-xs text-white bg-amber-500 px-2 py-0.5 rounded-full animate-pulse">{{ $stats['en_attente'] }}</span>
                @endif
            </div>
            <p class="text-2xl font-bold text-white">{{ $stats['total_commandes'] }}</p>
            <p class="text-slate-400 text-xs mt-0.5">Commandes totales</p>
        </div>
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-5 hover:bg-white/10 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-green-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-coins text-green-400"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['chiffre_affaires'], 0) }} DT</p>
            <p class="text-slate-400 text-xs mt-0.5">Chiffre d'affaires</p>
        </div>
    </div>

    {{-- UTILISATEURS PAR RÔLE --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-10">
        @php
            $roleColors = [
                'CLIENT'     => ['bg'=>'bg-blue-500/20', 'text'=>'text-blue-400',   'icon'=>'fa-user'],
                'ATELIER'    => ['bg'=>'bg-orange-500/20','text'=>'text-orange-400', 'icon'=>'fa-store'],
                'DONATEUR'   => ['bg'=>'bg-teal-500/20',  'text'=>'text-teal-400',   'icon'=>'fa-hand-holding-heart'],
                'COLLECTEUR' => ['bg'=>'bg-indigo-500/20','text'=>'text-indigo-400', 'icon'=>'fa-truck'],
                'RECYCLEUR'  => ['bg'=>'bg-emerald-500/20','text'=>'text-emerald-400','icon'=>'fa-recycle'],
                'ADMIN'      => ['bg'=>'bg-red-500/20',   'text'=>'text-red-400',    'icon'=>'fa-shield-alt'],
            ];
        @endphp
        @foreach($roleColors as $role => $style)
        <div class="bg-white/5 border border-white/10 rounded-xl p-4 text-center hover:bg-white/10 transition-all">
            <div class="w-9 h-9 rounded-xl {{ $style['bg'] }} flex items-center justify-center mx-auto mb-2">
                <i class="fas {{ $style['icon'] }} text-sm {{ $style['text'] }}"></i>
            </div>
            <p class="text-xl font-bold text-white">{{ $stats['by_role'][$role] ?? 0 }}</p>
            <p class="{{ $style['text'] }} text-[10px] font-semibold mt-0.5">{{ $role }}</p>
        </div>
        @endforeach
    </div>

    {{-- COMMANDES RÉCENTES + UTILISATEURS RÉCENTS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Commandes récentes --}}
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="font-bold text-white text-base flex items-center gap-2">
                    <i class="fas fa-receipt text-amber-400 text-sm"></i> Dernières commandes
                </h2>
                <a href="{{ route('commandes.index') }}" class="text-xs text-amber-400 hover:underline">Tout voir →</a>
            </div>
            <div class="divide-y divide-white/5">
                @foreach($recentCommandes as $commande)
                @php
                    $sColors = ['EN_ATTENTE'=>'text-amber-400 bg-amber-400/10','CONFIRMEE'=>'text-blue-400 bg-blue-400/10','EN_PREPARATION'=>'text-orange-400 bg-orange-400/10','EXPEDIEE'=>'text-indigo-400 bg-indigo-400/10','LIVREE'=>'text-green-400 bg-green-400/10','ANNULEE'=>'text-red-400 bg-red-400/10','EN_ATTENTE_PAIEMENT'=>'text-purple-400 bg-purple-400/10'];
                    $sc = $sColors[$commande->statut] ?? 'text-slate-400 bg-slate-400/10';
                @endphp
                <div class="px-6 py-3 flex items-center justify-between hover:bg-white/5 transition-colors">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ $commande->numero }}</p>
                        <p class="text-xs text-slate-400">{{ $commande->user?->full_name }} · {{ $commande->date_commande->format('d/m/Y') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $sc }}">{{ $commande->statut }}</span>
                        <span class="text-sm font-bold text-white">{{ number_format($commande->montant_total,2) }} DT</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Utilisateurs récents --}}
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="font-bold text-white text-base flex items-center gap-2">
                    <i class="fas fa-user-plus text-blue-400 text-sm"></i> Nouveaux utilisateurs
                </h2>
            </div>
            <div class="divide-y divide-white/5">
                @foreach($recentUsers as $u)
                <div class="px-6 py-3 flex items-center gap-3 hover:bg-white/5 transition-colors">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                         style="background: linear-gradient(135deg, #0d9488, #4ade80)">
                        {{ $u->initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-white truncate">{{ $u->full_name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $u->email }}</p>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-white/10 text-slate-300 flex-shrink-0">{{ $u->role }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
</div>
@endsection
