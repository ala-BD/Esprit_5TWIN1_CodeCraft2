@extends('layouts.app-auth')

@section('title', 'Créer un point de collecte')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <div class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">M1</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900">Nouveau point de collecte</h1>
    </div>

    <form action="{{ route('collecte.points.store') }}" method="POST" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="nom" class="mb-2 block text-sm font-medium text-slate-700">Nom du point</label>
                <input id="nom" name="nom" type="text" value="{{ old('nom') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" required>
                @error('nom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="adresse" class="mb-2 block text-sm font-medium text-slate-700">Adresse</label>
                <input id="adresse" name="adresse" type="text" value="{{ old('adresse') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" required>
                @error('adresse') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="ville" class="mb-2 block text-sm font-medium text-slate-700">Ville</label>
                <input id="ville" name="ville" type="text" value="{{ old('ville', 'Tunis') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" required>
                @error('ville') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="telephone" class="mb-2 block text-sm font-medium text-slate-700">Téléphone</label>
                <input id="telephone" name="telephone" type="text" value="{{ old('telephone') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                @error('telephone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="capacite_max_kg" class="mb-2 block text-sm font-medium text-slate-700">Capacité maximale (kg)</label>
                <input id="capacite_max_kg" name="capacite_max_kg" type="number" min="0" step="0.1" value="{{ old('capacite_max_kg', 0) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" required>
                @error('capacite_max_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="statut" class="mb-2 block text-sm font-medium text-slate-700">Statut</label>
                <select id="statut" name="statut" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" required>
                    <option value="ACTIF" {{ old('statut', 'ACTIF') === 'ACTIF' ? 'selected' : '' }}>Actif</option>
                    <option value="INACTIF" {{ old('statut') === 'INACTIF' ? 'selected' : '' }}>Inactif</option>
                    <option value="PLEIN" {{ old('statut') === 'PLEIN' ? 'selected' : '' }}>Plein</option>
                </select>
                @error('statut') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="latitude" class="mb-2 block text-sm font-medium text-slate-700">Latitude</label>
                <input id="latitude" name="latitude" type="number" step="0.0000001" value="{{ old('latitude') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="longitude" class="mb-2 block text-sm font-medium text-slate-700">Longitude</label>
                <input id="longitude" name="longitude" type="number" step="0.0000001" value="{{ old('longitude') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                @error('longitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="description" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">{{ old('description') }}</textarea>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('collecte.points.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Annuler</a>
            <button type="submit" class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark transition">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
