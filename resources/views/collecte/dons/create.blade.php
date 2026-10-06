@extends('layouts.app')

@section('title', 'Déclarer un don')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <div class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">M1 · Collecte</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900">Déclarer un don de vêtement</h1>
    </div>

    @if($points->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
            Aucun point de collecte actif n’accepte actuellement de dons.
            <a href="{{ route('collecte.points.index') }}" class="ml-1 font-semibold underline">Voir les points</a>
        </div>
    @else
        <form action="{{ route('collecte.dons.store') }}" method="POST" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="point_collecte_id" class="mb-2 block text-sm font-medium text-slate-700">Point de dépôt</label>
                <select id="point_collecte_id" name="point_collecte_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <option value="">Choisir un point actif</option>
                    @foreach($points as $point)
                        <option value="{{ $point->id }}" @selected(old('point_collecte_id') == $point->id)>{{ $point->nom }} · {{ $point->ville }} — {{ $point->adresse }}</option>
                    @endforeach
                </select>
                @error('point_collecte_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="type" class="mb-2 block text-sm font-medium text-slate-700">Type de vêtement</label>
                    <input id="type" name="type" value="{{ old('type') }}" placeholder="Ex. Chemise" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="matiere" class="mb-2 block text-sm font-medium text-slate-700">Matière</label>
                    <input id="matiere" name="matiere" value="{{ old('matiere') }}" placeholder="Ex. Coton" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('matiere') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="taille" class="mb-2 block text-sm font-medium text-slate-700">Taille</label>
                    <input id="taille" name="taille" value="{{ old('taille') }}" placeholder="Ex. M, 40, unique" required maxlength="50" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('taille') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="etat" class="mb-2 block text-sm font-medium text-slate-700">État</label>
                    <select id="etat" name="etat" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <option value="">Choisir l’état</option>
                        @foreach(['Neuf', 'Très bon', 'Bon', 'À réparer'] as $etat)
                            <option value="{{ $etat }}" @selected(old('etat') === $etat)>{{ $etat }}</option>
                        @endforeach
                    </select>
                    @error('etat') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="photo_url" class="mb-2 block text-sm font-medium text-slate-700">Lien vers une photo <span class="font-normal text-slate-500">(facultatif)</span></label>
                    <input id="photo_url" name="photo_url" type="url" value="{{ old('photo_url') }}" placeholder="https://…" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('photo_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('collecte.dons.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Annuler</a>
                <button type="submit" class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark">Enregistrer le don</button>
            </div>
        </form>
    @endif
</div>
@endsection