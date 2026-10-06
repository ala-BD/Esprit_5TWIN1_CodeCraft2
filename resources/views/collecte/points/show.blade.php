@extends('layouts.app')

@section('title', 'Détails du point')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">M1</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $pointCollecte->nom }}</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('collecte.points.edit', $pointCollecte) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Modifier</a>
            <form action="{{ route('collecte.points.destroy', $pointCollecte) }}" method="POST" onsubmit="return confirm('Supprimer ce point ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Supprimer</button>
            </form>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Informations</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div>
                    <dt class="text-slate-500">Ville</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $pointCollecte->ville }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Adresse</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $pointCollecte->adresse }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Téléphone</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $pointCollecte->telephone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Capacité</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ number_format($pointCollecte->capacite_max_kg, 0, ',', ' ') }} kg</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Statut</h2>
            @php $badge = $pointCollecte->statut_badge; @endphp
            <div class="mt-4 inline-flex rounded-full px-3 py-1.5 text-sm font-semibold {{ $badge['class'] }}">
                {{ $badge['label'] }}
            </div>

            <div class="mt-6">
                <p class="text-sm text-slate-500">Description</p>
                <p class="mt-2 text-sm text-slate-700">{{ $pointCollecte->description ?? 'Aucune description fournie.' }}</p>
            </div>

            <div class="mt-6">
                <p class="text-sm text-slate-500">Coordonnées</p>
                <p class="mt-2 text-sm text-slate-700">
                    {{ $pointCollecte->latitude ?? '—' }} / {{ $pointCollecte->longitude ?? '—' }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
