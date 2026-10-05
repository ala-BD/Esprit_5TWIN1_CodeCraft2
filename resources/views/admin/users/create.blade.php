@extends('admin.layouts.admin')

@section('title', 'Ajouter un utilisateur')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('admin.users.index') }}" class="hover:text-slate-900">Utilisateurs</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Ajouter</span>
@endsection

@section('admin-content')

<div>

    {{-- Header --}}
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Ajouter un utilisateur</h1>
        <p class="text-slate-500 mt-0.5">Créez un compte et attribuez-lui un rôle sur la plateforme.</p>
    </div>

    {{-- Formulaire --}}
    <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
        @csrf

        @include('admin.users._form')

        {{-- Boutons --}}
        <div class="flex items-center justify-end gap-2 mt-4">
            <a href="{{ route('admin.users.index') }}" class="a-btn">Annuler</a>
            <button type="submit" class="a-btn a-btn-primary">Créer l'utilisateur</button>
        </div>
    </form>
</div>

@endsection
