@extends('layouts.app-auth')

@section('title', 'Suivi du don')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">M1 · Suivi du don</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $don->type }}</h1>
            <p class="mt-1 text-slate-500">Enregistré le {{ $don->date_depot?->format('d/m/Y') }}</p>
        </div>
        @php $badge = $don->statut_badge; @endphp
        <span class="rounded-full px-3 py-1.5 text-sm font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Vêtement</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div><dt class="text-slate-500">Matière</dt><dd class="mt-1 font-medium text-slate-900">{{ $don->matiere }}</dd></div>
                <div><dt class="text-slate-500">Taille</dt><dd class="mt-1 font-medium text-slate-900">{{ $don->taille }}</dd></div>
                <div><dt class="text-slate-500">État</dt><dd class="mt-1 font-medium text-slate-900">{{ $don->etat }}</dd></div>
                @if($don->photo_url)
                    <div><dt class="text-slate-500">Photo</dt><dd class="mt-1"><a href="{{ $don->photo_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-primary underline">Voir la photo</a></dd></div>
                @endif
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Dépôt</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div><dt class="text-slate-500">Point de collecte</dt><dd class="mt-1 font-medium text-slate-900">{{ $don->pointCollecte?->nom ?? 'Point supprimé' }}</dd></div>
                @if($don->pointCollecte)
                    <div><dt class="text-slate-500">Adresse</dt><dd class="mt-1 font-medium text-slate-900">{{ $don->pointCollecte->adresse }}, {{ $don->pointCollecte->ville }}</dd></div>
                @endif
                <div><dt class="text-slate-500">Référence QR</dt><dd class="mt-1 break-all font-mono text-xs text-slate-700">{{ $don->qr_code }}</dd></div>
                @if($canManage)
                    <div><dt class="text-slate-500">Donateur</dt><dd class="mt-1 font-medium text-slate-900">{{ $don->user?->full_name ?: ($don->user?->name ?? 'Compte supprimé') }}</dd></div>
                @endif
            </dl>
        </section>
    </div>

    @if($canManage)
        <form action="{{ route('collecte.dons.statut', ['don' => $don->id]) }}" method="POST" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PATCH')
            <div class="min-w-52 flex-1">
                <label for="statut" class="mb-2 block text-sm font-medium text-slate-700">Mettre à jour le statut</label>
                <select id="statut" name="statut" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @foreach(['DEPOSE' => 'Déposé', 'EN_TRI' => 'En tri', 'VENDU' => 'Vendu', 'UPCYCLING' => 'Upcycling', 'RECYCLE' => 'Recyclé'] as $value => $label)
                        <option value="{{ $value }}" @selected($don->statut === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('statut') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark">Enregistrer le statut</button>
        </form>
    @endif

    <div class="mt-6">
        <a href="{{ route('collecte.dons.index') }}" class="text-sm font-semibold text-primary hover:text-primary-dark">← Retour aux dons</a>
    </div>
</div>
@endsection