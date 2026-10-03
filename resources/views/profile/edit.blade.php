@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
<div class="min-h-screen bg-[#FAFAF8] py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="font-display text-3xl font-bold text-gray-900">Mon profil</h1>
            <p class="text-gray-500 mt-1">Gérez vos informations personnelles et la sécurité de votre compte.</p>
        </div>

        {{-- Informations du profil --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        {{-- Mot de passe --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            @include('profile.partials.update-password-form')
        </div>

        {{-- Supprimer le compte --}}
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 p-6 sm:p-8">
            @include('profile.partials.delete-user-form')
        </div>

    </div>
</div>
@endsection
