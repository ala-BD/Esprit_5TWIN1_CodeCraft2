@extends('admin.layouts.admin')

@section('title', 'Modifier ' . $user->full_name)

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('admin.users.index') }}" class="hover:text-slate-900">Utilisateurs</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('admin.users.show', $user) }}" class="hover:text-slate-900 truncate">{{ $user->full_name }}</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Modifier</span>
@endsection

@section('admin-content')

<div>

    {{-- Header --}}
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Modifier l'utilisateur</h1>
        <p class="text-slate-500 mt-0.5">{{ $user->full_name }} · {{ $user->email }}</p>
    </div>

    {{-- Formulaire --}}
    <form method="POST" action="{{ route('admin.users.update', $user) }}" novalidate>
        @csrf @method('PUT')

        @include('admin.users._form')

        {{-- Boutons --}}
        <div class="flex items-center justify-end gap-2 mt-4">
            <a href="{{ route('admin.users.show', $user) }}" class="a-btn">Annuler</a>
            <button type="submit" class="a-btn a-btn-primary">Enregistrer les modifications</button>
        </div>
    </form>
</div>

@endsection
