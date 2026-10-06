@extends('layouts.app')

@section('title', 'Dashboard collecte')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="mb-8 flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">Module M1</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Points de collecte</h1>
        </div>
        <a href="{{ route('collecte.points.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark transition">
            <i class="fa-solid fa-plus"></i>
            Nouveau point
        </a>
    </div>

    <div class="grid gap-5 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm text-emerald-700">Actifs</p>
            <p class="mt-3 text-3xl font-bold text-emerald-700">{{ $stats['actifs'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
            <p class="text-sm text-slate-600">Inactifs</p>
            <p class="mt-3 text-3xl font-bold text-slate-700">{{ $stats['inactifs'] }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm text-amber-700">Capacité</p>
            <p class="mt-3 text-3xl font-bold text-amber-700">{{ number_format($stats['capacite_totale'], 0, ',', ' ') }} kg</p>
        </div>
    </div>

    <div class="mt-10 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Derniers points</h2>
            <a href="{{ route('collecte.points.index') }}" class="text-sm font-medium text-primary hover:text-primary-dark">Voir tout</a>
        </div>

        @if($points->isEmpty())
            <div class="p-10 text-center text-slate-500">
                Aucun point de collecte n’a encore été enregistré.
            </div>
        @else
            <div class="divide-y divide-slate-200">
                @foreach($points as $point)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $point->nom }}</p>
                            <p class="text-sm text-slate-500">{{ $point->ville }} · {{ $point->adresse }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @php $badge = $point->statut_badge; @endphp
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                            <a href="{{ route('collecte.points.show', $point) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                Détails
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
