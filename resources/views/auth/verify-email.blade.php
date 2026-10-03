@extends('layouts.app')

@section('title', 'Vérification e-mail')

@section('content')
<div class="min-h-screen bg-[#FAFAF8] flex items-center justify-center p-6">
    <div class="w-full max-w-md fade-in">

        {{-- Card principale --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center">

            {{-- Icône animée --}}
            <div class="relative inline-block mb-8">
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-primary-DEFAULT to-secondary-dark flex items-center justify-center mx-auto shadow-lg">
                    <i class="fas fa-envelope-open-text text-white text-3xl"></i>
                </div>
                <div class="absolute -top-1 -right-1 w-8 h-8 rounded-full bg-amber-400 flex items-center justify-center shadow-md animate-bounce">
                    <i class="fas fa-exclamation text-white text-sm"></i>
                </div>
            </div>

            {{-- Logo --}}
            <div class="flex items-center justify-center gap-2 mb-6">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-primary-dark to-primary-light flex items-center justify-center">
                    <span class="text-white font-black text-xs">R</span>
                </div>
                <span class="font-display font-bold text-xl text-primary-dark">RETISS</span>
            </div>

            {{-- Titre --}}
            <h1 class="font-display text-2xl font-bold text-gray-900 mb-3">
                Vérifiez votre adresse e-mail
            </h1>

            <p class="text-gray-500 text-sm leading-relaxed mb-6">
                Merci de vous être inscrit sur RETISS ! Avant de continuer, veuillez vérifier votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer.
            </p>

            {{-- Email de l'utilisateur --}}
            <div class="bg-gray-50 rounded-xl px-4 py-3 mb-6 flex items-center justify-center gap-2">
                <i class="fas fa-envelope text-primary-DEFAULT text-sm"></i>
                <span class="text-gray-700 text-sm font-medium">{{ Auth::user()->email }}</span>
            </div>

            {{-- Message de succès renvoi --}}
            @if (session('status') == 'verification-link-sent')
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center gap-3 text-left">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-green-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-green-800 font-semibold text-sm">Email renvoyé !</p>
                        <p class="text-green-600 text-xs mt-0.5">
                            Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Bouton renvoi --}}
            <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
                @csrf
                <button type="submit"
                        class="w-full bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white
                               font-bold py-3.5 px-6 rounded-xl hover:shadow-lg hover:scale-[1.01]
                               active:scale-[0.99] transition-all duration-200
                               flex items-center justify-center gap-2">
                    <i class="fas fa-paper-plane"></i>
                    Renvoyer l'e-mail de vérification
                </button>
            </form>

            {{-- Séparateur --}}
            <div class="flex items-center gap-4 my-5">
                <div class="flex-1 h-px bg-gray-200"></div>
                <span class="text-xs text-gray-400 font-medium">ou</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            {{-- Déconnexion --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full border-2 border-gray-200 text-gray-600 font-semibold
                               py-3 px-6 rounded-xl hover:border-red-200 hover:text-red-500
                               transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-sign-out-alt"></i>
                    Se déconnecter
                </button>
            </form>
        </div>

        {{-- Aide bas --}}
        <div class="mt-6 text-center">
            <p class="text-gray-400 text-xs leading-relaxed">
                Vous n'avez pas reçu l'email ? Vérifiez votre dossier
                <span class="font-semibold text-gray-600">Spam</span> ou
                <span class="font-semibold text-gray-600">Courrier indésirable</span>.
            </p>
        </div>

    </div>
</div>
@endsection
