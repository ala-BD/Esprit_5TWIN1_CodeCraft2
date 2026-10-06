@extends('logistique.layouts.logistique')

@section('title', 'Nouvelle tournée')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('logistique.tournees.index') }}" class="hover:text-slate-900">Tournées</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Nouvelle</span>
@endsection

@section('logistique-content')

{{-- Header --}}
<div class="mb-5">
    <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Nouvelle tournée</h1>
    <p class="text-slate-500 mt-0.5">Planifiez la tournée, puis ajoutez ses collectes et livraisons.</p>
</div>

{{-- Formulaire --}}
<form method="POST" action="{{ route('logistique.tournees.store') }}" novalidate>
    @csrf

    @include('logistique.tournees._form')

    {{-- Boutons --}}
    <div class="flex items-center justify-end gap-2 mt-4">
        <a href="{{ route('logistique.tournees.index') }}" class="a-btn">Annuler</a>
        <button type="submit" class="a-btn a-btn-primary">Enregistrer la tournée</button>
    </div>
</form>

@endsection
