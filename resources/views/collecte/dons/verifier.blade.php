@extends('layouts.app-auth')

@section('title', 'Vérification du don')

@section('content')
<main class="mx-auto max-w-3xl px-4 py-10">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-primary px-6 py-6 text-white">
            <p class="text-sm font-semibold uppercase tracking-[0.2em]">TextileCycle · Vérification</p>
            <h1 class="mt-2 text-2xl font-bold">Don de vêtement vérifié</h1>
        </div>

        <div class="grid gap-6 p-6 sm:grid-cols-[1fr_auto]">
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-slate-500">Type de vêtement</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $don->type }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Statut</p>
                    @php $badge = $don->statut_badge; @endphp
                    <span class="mt-1 inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                </div>
                @if($don->couleur)
                    <div>
                        <p class="text-sm text-slate-500">Couleur</p>
                        <p class="mt-1 font-medium text-slate-900">{{ $don->couleur }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-sm text-slate-500">Date de dépôt</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $don->date_depot?->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Référence</p>
                    <p class="mt-1 break-all font-mono text-xs text-slate-700">{{ $don->qr_code }}</p>
                </div>
            </div>

            @if($don->photo_url)
                @php
                    $photoIsExternal = filter_var($don->photo_url, FILTER_VALIDATE_URL);
                    $photoUrl = $photoIsExternal ? $don->photo_url : '/storage/' . ltrim($don->photo_url, '/');
                @endphp
                <img src="{{ $photoUrl }}" alt="Photo du vêtement donné" class="max-h-72 w-full rounded-xl border border-slate-200 object-contain sm:w-56">
            @endif
        </div>
    </section>
</main>
@endsection
