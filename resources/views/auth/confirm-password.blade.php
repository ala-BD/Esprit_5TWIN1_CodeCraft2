@extends('layouts.app')

@section('title', 'Confirmer le mot de passe')

@section('content')
<div class="min-h-screen flex">

    {{-- Panneau gauche --}}
    <div class="hidden lg:flex lg:w-1/2 auth-bg relative overflow-hidden flex-col justify-between p-12">
        <div class="absolute top-[-60px] right-[-60px] w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
        <div class="relative flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                <span class="text-white font-black text-base">R</span>
            </div>
            <span class="font-display font-bold text-2xl text-white tracking-tight">RETISS</span>
        </div>
        <div class="relative flex-1 flex flex-col justify-center py-12">
            <div class="w-20 h-20 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center mb-8">
                <i class="fas fa-shield-alt text-white text-3xl"></i>
            </div>
            <h2 class="font-display text-4xl font-bold text-white leading-tight mb-4">
                Zone <span class="text-secondary-DEFAULT">sécurisée</span>
            </h2>
            <p class="text-white/70 text-lg leading-relaxed max-w-md">
                Confirmez votre mot de passe pour accéder à cette section protégée.
            </p>
        </div>
        <div class="relative flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/15">
            <i class="fas fa-lock text-secondary-DEFAULT text-xl flex-shrink-0"></i>
            <p class="text-white/70 text-sm">Votre session est <strong class="text-white">chiffrée et sécurisée</strong>.</p>
        </div>
    </div>

    {{-- Panneau droit --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10 bg-[#FAFAF8]">
        <div class="w-full max-w-md fade-in">

            <div class="w-16 h-16 rounded-2xl bg-primary-DEFAULT/10 border border-primary-DEFAULT/20 flex items-center justify-center mb-6">
                <i class="fas fa-shield-alt text-primary-DEFAULT text-2xl"></i>
            </div>

            <div class="mb-8">
                <h1 class="font-display text-3xl font-bold text-gray-900 mb-2">Confirmation requise</h1>
                <p class="text-gray-500 text-sm leading-relaxed">
                    {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                </p>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
                    <div>@foreach($errors->all() as $error)<p class="text-red-600 text-sm">{{ $error }}</p>@endforeach</div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.confirm') }}" novalidate>
                @csrf
                <div class="mb-5">
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               class="w-full pl-11 pr-4 py-3 rounded-xl border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
                                      focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                      transition-all duration-200 text-gray-700">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white
                               font-bold py-3.5 px-6 rounded-xl hover:shadow-lg hover:scale-[1.01]
                               transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    Confirmer
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
