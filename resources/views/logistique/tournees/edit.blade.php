@extends('logistique.layouts.logistique')

@section('title', 'Modifier la tournée')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('logistique.tournees.index') }}" class="hover:text-slate-900">Tournées</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('logistique.tournees.show', $tournee) }}" class="hover:text-slate-900 truncate num">{{ $tournee->date->format('d/m/Y') }}</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Modifier</span>
@endsection

@section('logistique-content')

{{-- Header --}}
<div class="mb-5">
    <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Modifier la tournée</h1>
    <p class="text-slate-500 mt-0.5 num">{{ $tournee->zone }}, le {{ $tournee->date->format('d/m/Y') }}</p>
</div>

{{-- Formulaire --}}
<form method="POST" action="{{ route('logistique.tournees.update', $tournee) }}" novalidate>
    @csrf @method('PUT')

    @include('logistique.tournees._form')

    {{-- Boutons --}}
    <div class="flex items-center justify-end gap-2 mt-4">
        <a href="{{ route('logistique.tournees.show', $tournee) }}" class="a-btn">Annuler</a>
        <button type="submit" class="a-btn a-btn-primary">Enregistrer les modifications</button>
    </div>
</form>

@endsection
