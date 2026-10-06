@extends('layouts.app')

@section('title', 'Points de collecte')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">M1</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Points de collecte</h1>
        </div>
        <a href="{{ route('collecte.points.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark transition">
            <i class="fa-solid fa-plus"></i>
            Nouveau point
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nom</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ville</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Capacité</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
            @forelse($points as $point)
                <tr>
                    <td class="px-6 py-4">
                        <div class="font-semibold text-slate-900">{{ $point->nom }}</div>
                        <div class="text-xs text-slate-500">{{ $point->adresse }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-700">{{ $point->ville }}</td>
                    <td class="px-6 py-4 text-sm text-slate-700">{{ number_format($point->capacite_max_kg, 0, ',', ' ') }} kg</td>
                    <td class="px-6 py-4">
                        @php $badge = $point->statut_badge; @endphp
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('collecte.points.show', ['point' => $point->id]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Voir</a>
                            <a href="{{ route('collecte.points.edit', ['point' => $point->id]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Modifier</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-slate-500">Aucun point de collecte pour le moment.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($points->hasPages())
        <div class="mt-6">
            {{ $points->links() }}
        </div>
    @endif
</div>
@endsection
