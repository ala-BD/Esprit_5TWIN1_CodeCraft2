@extends('layouts.app-auth')

@section('title', Auth::user()->role === 'DONATEUR' ? 'Mes dons' : 'Dons reçus')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">M1 · Collecte</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ Auth::user()->role === 'DONATEUR' ? 'Mes dons' : 'Dons reçus' }}</h1>
        </div>
        @if(Auth::user()->role === 'DONATEUR')
            <a href="{{ route('collecte.dons.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark">
                <i class="fa-solid fa-plus"></i> Déclarer un don
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Vêtement</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Point de collecte</th>
                        @if(Auth::user()->role !== 'DONATEUR')
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Donateur</th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Détail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($dons as $don)
                        @php $badge = $don->statut_badge; @endphp
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">{{ $don->type }}</p>
                                <p class="text-sm text-slate-500">{{ $don->matiere }} · taille {{ $don->taille }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ $don->pointCollecte?->nom ?? 'Point supprimé' }}
                                @if($don->pointCollecte)<p class="text-xs text-slate-500">{{ $don->pointCollecte->ville }}</p>@endif
                            </td>
                            @if(Auth::user()->role !== 'DONATEUR')
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $don->user?->full_name ?: ($don->user?->name ?? 'Compte supprimé') }}</td>
                            @endif
                            <td class="px-6 py-4 text-sm text-slate-700">{{ $don->date_depot?->format('d/m/Y') }}</td>
                            <td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('collecte.dons.show', ['don' => $don->id]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Ouvrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ Auth::user()->role === 'DONATEUR' ? 5 : 6 }}" class="px-6 py-12 text-center text-slate-500">
                                Aucun don enregistré pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($dons->hasPages())
        <div class="mt-6">{{ $dons->links() }}</div>
    @endif
</div>
@endsection