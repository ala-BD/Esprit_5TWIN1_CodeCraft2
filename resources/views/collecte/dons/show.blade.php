@extends('layouts.app-auth')

@section('title', 'Suivi du don')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>

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
                @if($don->couleur)
                    <div><dt class="text-slate-500">Couleur</dt><dd class="mt-1 font-medium text-slate-900">{{ $don->couleur }}</dd></div>
                @endif
                <div><dt class="text-slate-500">Taille</dt><dd class="mt-1 font-medium text-slate-900">{{ $don->taille }}</dd></div>
                <div><dt class="text-slate-500">État</dt><dd class="mt-1 font-medium text-slate-900">{{ $don->etat }}</dd></div>
                @php
                    $photos = [
                        [
                            'label' => 'Photo originale',
                            'path' => $don->photo_originale_url ?: (!$don->photo_amelioree_url ? $don->photo_url : null),
                        ],
                        ['label' => 'Photo améliorée par l’IA', 'path' => $don->photo_amelioree_url],
                    ];
                @endphp
                @if(collect($photos)->contains(fn ($photo) => filled($photo['path'])))
                    @php
                        $photos = collect($photos)->filter(fn ($photo) => filled($photo['path']));
                    @endphp
                    <div>
                        <dt class="text-slate-500">Photos du vêtement</dt>
                        <dd class="mt-2 grid gap-3 sm:grid-cols-2">
                            @foreach($photos as $photo)
                                @php
                                    $photoIsExternal = filter_var($photo['path'], FILTER_VALIDATE_URL);
                                    $photoUrl = $photoIsExternal ? $photo['path'] : '/storage/' . ltrim($photo['path'], '/');
                                @endphp
                                <div class="rounded-xl border {{ $don->photo_url === $photo['path'] ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200' }} p-3">
                                    <p class="mb-2 text-xs font-semibold text-slate-700">
                                        {{ $photo['label'] }}
                                        @if($don->photo_url === $photo['path'])
                                            <span class="ml-1 text-primary">· utilisée pour le don</span>
                                        @endif
                                    </p>
                                    @if($photoIsExternal)
                                        <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-primary underline">Voir la photo</a>
                                    @else
                                        <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer">
                                            <img src="{{ $photoUrl }}" alt="{{ $photo['label'] }}" class="max-h-72 w-full rounded-lg object-contain">
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Suivi</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div>
                    <dt class="text-slate-500">QR code du don</dt>
                    <dd class="mt-2 flex flex-col items-start gap-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            {!! QrCode::size(180)->margin(1)->generate(route('collecte.dons.verifier', ['qrCode' => $don->qr_code])) !!}
                        </div>
                        <span class="break-all font-mono text-xs text-slate-700">{{ $don->qr_code }}</span>
                        <a href="{{ route('collecte.dons.verifier', ['qrCode' => $don->qr_code]) }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary underline">
                            Ouvrir la page de vérification publique
                        </a>
                    </dd>
                </div>
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