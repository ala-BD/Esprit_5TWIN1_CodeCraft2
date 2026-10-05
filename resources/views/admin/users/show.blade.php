@extends('admin.layouts.admin')

@section('title', $user->full_name)

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('admin.users.index') }}" class="hover:text-slate-900">Utilisateurs</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium truncate">{{ $user->full_name }}</span>
@endsection

@section('admin-content')

<div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-5">
        <span class="a-avatar w-12 h-12 text-base">{{ $user->initials ?: '?' }}</span>

        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-semibold text-slate-900 tracking-tight truncate">{{ $user->full_name }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                @include('admin.users._role-badge', ['role' => $user->role])
                <span class="text-slate-500">{{ $user->actif ? 'Compte actif' : 'Compte désactivé' }}</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @unless($user->is(Auth::user()))
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                  data-confirm-title="Supprimer cet utilisateur ?"
                  data-confirm-message="Le compte de {{ $user->full_name }} sera supprimé définitivement. Cette action est irréversible."
                  data-confirm-label="Supprimer">
                @csrf @method('DELETE')
                <button type="submit" class="a-btn a-btn-danger">
                    <i class="fas fa-trash-can"></i> Supprimer
                </button>
            </form>
            @endunless
            <a href="{{ route('admin.users.edit', $user) }}" class="a-btn a-btn-primary">
                <i class="fas fa-pen"></i> Modifier
            </a>
        </div>
    </div>

    {{-- Informations --}}
    <div class="a-card">
        <h2 class="font-semibold text-slate-900 px-5 py-3 border-b border-slate-200">Informations du compte</h2>

        <dl class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-x-8 gap-y-4 p-5">
            <div>
                <dt class="text-slate-500">Prénom</dt>
                <dd class="font-medium text-slate-900 mt-0.5">{{ $user->prenom ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Nom</dt>
                <dd class="font-medium text-slate-900 mt-0.5">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Adresse e-mail</dt>
                <dd class="font-medium text-slate-900 mt-0.5 break-all">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Téléphone</dt>
                <dd class="num font-medium text-slate-900 mt-0.5">{{ $user->telephone ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">E-mail vérifié</dt>
                <dd class="num font-medium text-slate-900 mt-0.5">{{ $user->email_verified_at?->format('d/m/Y') ?? 'Non' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Inscription</dt>
                <dd class="num font-medium text-slate-900 mt-0.5">{{ $user->created_at->format('d/m/Y à H:i') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Dernière modification</dt>
                <dd class="num font-medium text-slate-900 mt-0.5">{{ $user->updated_at->format('d/m/Y à H:i') }}</dd>
            </div>
        </dl>
    </div>
</div>

@endsection
