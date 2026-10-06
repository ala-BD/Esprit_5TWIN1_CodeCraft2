@extends('layouts.app-auth')

@section('title', ($isEdit ? 'Modifier l\'adresse' : 'Ajouter une adresse') . ' — RETISS')

@php
    $mapsKey = config('services.google_maps.key', '');
    $mapsReady = $mapsKey && $mapsKey !== 'YOUR_GOOGLE_MAPS_API_KEY';
    // Initial map center: if editing and coords exist, use them; else default to Tunisia
    $initLat = old('latitude', $adresse->latitude ?? 36.8065);
    $initLng = old('longitude', $adresse->longitude ?? 10.1815);
    $initZoom = ($adresse->latitude && $adresse->longitude) ? 14 : 7;
@endphp

@section('styles')
@if(!$mapsReady)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endif
<style>
    /* ============ PAGE LAYOUT ============ */
    .address-form-page {
        min-height: 100vh;
        background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 40%, #f0f4ff 100%);
        padding-top: 72px;
    }

    /* ============ HERO ============ */
    .address-form-hero {
        background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
        position: relative;
        overflow: hidden;
        padding: 2.5rem 0 5rem;
    }
    .address-form-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(ellipse at 80% 50%, rgba(13,148,136,0.25) 0%, transparent 60%),
            radial-gradient(ellipse at 20% 80%, rgba(74,222,128,0.1) 0%, transparent 50%);
        pointer-events: none;
    }
    .hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 40px 40px;
        pointer-events: none;
    }

    /* ============ CARD WRAP ============ */
    .form-card-wrap {
        margin-top: -3.5rem;
        position: relative;
        z-index: 10;
    }

    /* ============ MAP CARD ============ */
    .map-card {
        background: #fff;
        border-radius: 1.5rem;
        border: 1.5px solid #e2e8f0;
        box-shadow: 0 4px 24px rgba(15,23,42,0.07);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .map-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(135deg, #f0fdf9, #f8fafc);
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    #map-container {
        position: relative;
        height: 380px;
        width: 100%;
    }
    #map {
        width: 100%; height: 100%;
        background: #e2e8f0;
        z-index: 1;
    }
    /* Search box inside the map */
    #map-search-wrap {
        position: absolute;
        top: 12px;
        left: 12px;
        right: 12px;
        max-width: 520px;
        z-index: 999;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .search-input-group {
        display: flex;
        gap: 8px;
        width: 100%;
    }
    #map-search {
        flex: 1;
        padding: 0.75rem 1rem 0.75rem 2.6rem;
        border-radius: 0.875rem;
        border: 1px solid rgba(0,0,0,0.08);
        box-shadow: 0 4px 16px rgba(15,23,42,0.18);
        font-size: 0.875rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2394a3b8' viewBox='0 0 24 24'%3E%3Cpath d='M10 2a8 8 0 105.293 14.293l4.707 4.707 1.414-1.414-4.707-4.707A8 8 0 0010 2zm0 2a6 6 0 110 12A6 6 0 0110 4z'/%3E%3C/svg%3E") no-repeat 0.85rem center / 1.1rem;
        transition: box-shadow 0.2s;
    }
    #map-search:focus {
        box-shadow: 0 4px 20px rgba(13,148,136,0.3);
        border-color: #0d9488;
    }
    #map-search::placeholder { color: #94a3b8; }

    /* Search suggestions dropdown (for Leaflet / Nominatim) */
    #map-search-results {
        display: none;
        background: #fff;
        border-radius: 0.875rem;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        max-height: 220px;
        overflow-y: auto;
    }
    .search-result-item {
        padding: 0.65rem 1rem;
        font-size: 0.8rem;
        color: #334155;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: background 0.15s;
    }
    .search-result-item:hover {
        background: #f0fdf9;
        color: #0d9488;
    }

    /* Recenter button */
    #recenter-btn {
        padding: 0.75rem 1rem;
        border-radius: 0.875rem;
        background: #fff;
        box-shadow: 0 4px 16px rgba(15,23,42,0.18);
        border: 1px solid rgba(0,0,0,0.08);
        cursor: pointer;
        transition: all 0.2s;
        color: #0d9488;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #recenter-btn:hover { background: #f0fdf9; box-shadow: 0 4px 20px rgba(13,148,136,0.25); }

    /* Chosen address strip */
    #map-chosen-strip {
        display: none;
        padding: 0.75rem 1.25rem;
        background: linear-gradient(135deg, #f0fdf9, #f8fafc);
        border-top: 1px solid #d1fae5;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: #0f766e;
    }
    #map-chosen-strip.visible { display: flex; }

    /* Mode badges */
    .map-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .map-badge-google {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .map-badge-osm {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    /* Info banner for map mode */
    .map-mode-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.75rem;
        color: #64748b;
    }

    /* ============ FORM INPUTS ============ */
    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        letter-spacing: 0.01em;
    }
    .form-input {
        width: 100%;
        padding: 0.85rem 1rem 0.85rem 2.75rem;
        border-radius: 0.875rem;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        font-size: 0.9rem;
        color: #0f172a;
        transition: all 0.2s;
        outline: none;
        font-family: 'Inter', sans-serif;
    }
    .form-input:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13,148,136,0.12);
    }
    .form-input.autofilled {
        border-color: #10b981;
        background: #f0fdf9;
        animation: pulseHighlight 1.5s ease-out;
    }
    @keyframes pulseHighlight {
        0% { transform: scale(1.01); background-color: #d1fae5; }
        100% { transform: scale(1); background-color: #fff; }
    }
    .input-wrap { position: relative; }
    .input-icon {
        position: absolute;
        left: 1rem; top: 50%;
        transform: translateY(-50%);
        color: #94a3b8; font-size: 0.85rem;
        pointer-events: none; transition: color 0.2s;
    }
    .input-wrap:focus-within .input-icon { color: #0d9488; }

    /* ============ PRESET PILLS ============ */
    .tag-preset-btn {
        padding: 0.35rem 0.75rem;
        border-radius: 0.6rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .tag-preset-btn:hover {
        background: #f0fdf9; border-color: #0d9488; color: #0d9488;
    }

    /* ============ INFO BOX ============ */
    .info-box {
        background: linear-gradient(135deg, #f0fdf9 0%, #f8fafc 100%);
        border: 1px solid #d1fae5;
        border-radius: 1.25rem;
        padding: 1.25rem;
    }

    /* ============ MAP LOADING OVERLAY ============ */
    #map-loading {
        position: absolute;
        inset: 0;
        background: rgba(248,250,252,0.92);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        z-index: 1000;
        font-size: 0.85rem;
        font-weight: 600;
        color: #0d9488;
        border-radius: 0;
    }
    .map-spinner {
        width: 36px; height: 36px;
        border: 3.5px solid #d1fae5;
        border-top-color: #0d9488;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Custom Leaflet Marker Pulse */
    .custom-map-pin {
        width: 24px;
        height: 24px;
        background: #0d9488;
        border: 3px solid #ffffff;
        border-radius: 50%;
        box-shadow: 0 0 12px rgba(13,148,136,0.6);
        cursor: grab;
    }
</style>
@endsection

@section('content')
<div class="address-form-page">

    {{-- ===== HERO ===== --}}
    <div class="address-form-hero">
        <div class="hero-grid"></div>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <a href="{{ route('adresses.index') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    Mes adresses
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-medium">
                    {{ $isEdit ? 'Modifier l\'adresse' : 'Nouvelle adresse' }}
                </span>
            </div>
            <h1 class="font-display text-3xl font-extrabold text-white mb-2">
                {{ $isEdit ? 'Modifier votre adresse' : 'Ajouter une adresse' }}
            </h1>
            <p class="text-white/70 text-sm">
                Sélectionnez votre emplacement directement sur la carte interactive — les coordonnées et l'adresse se remplissent instantanément.
            </p>
        </div>
    </div>

    {{-- ===== MAIN CONTENT ===== --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 form-card-wrap pb-16">

        {{-- ===== INTERACTIVE MAP CARD (Google Maps OR OpenStreetMap) ===== --}}
        <div class="map-card">
            <div class="map-card-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-600 flex items-center justify-center">
                        <i class="fas fa-map-marked-alt text-sm"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-slate-800 text-sm">Localisation interactive sur la carte</h3>
                            @if($mapsReady)
                                <span class="map-badge map-badge-google">
                                    <i class="fab fa-google text-[10px]"></i> Google Maps API
                                </span>
                            @else
                                <span class="map-badge map-badge-osm">
                                    <i class="fas fa-globe text-[10px]"></i> Carte active (OSM)
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-400">Cliquez n'importe où sur la carte ou recherchez un lieu pour remplir le formulaire</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-lg">
                        <i class="fas fa-hand-pointer text-teal-600"></i> Clic = Placer l'épingle
                    </span>
                    <span class="hidden sm:inline-flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-lg">
                        <i class="fas fa-magic text-teal-600"></i> Remplissage auto
                    </span>
                </div>
            </div>

            @if(!$mapsReady)
            <div class="map-mode-banner">
                <span class="flex items-center gap-1.5 text-slate-600">
                    <i class="fas fa-info-circle text-teal-600"></i>
                    Mode interactif actif sans clé requise. Pour activer Google Maps officiel, ajoutez votre clé dans <code>.env</code>.
                </span>
                <span class="font-mono text-[11px] text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                    GOOGLE_MAPS_API_KEY
                </span>
            </div>
            @endif

            <div id="map-container">
                {{-- Loading overlay --}}
                <div id="map-loading">
                    <div class="map-spinner"></div>
                    <span id="map-loading-text">Initialisation de la carte…</span>
                </div>

                {{-- Search box inside map --}}
                <div id="map-search-wrap">
                    <div class="search-input-group">
                        <input id="map-search"
                               type="text"
                               placeholder="Rechercher une adresse, ville, quartier…"
                               autocomplete="off">
                        <button id="recenter-btn" type="button" onclick="recenterMap()" title="Centrer sur ma position GPS">
                            <i class="fas fa-crosshairs"></i>
                        </button>
                    </div>
                    <div id="map-search-results"></div>
                </div>

                {{-- Actual map canvas --}}
                <div id="map"></div>
            </div>

            {{-- Chosen address strip --}}
            <div id="map-chosen-strip">
                <i class="fas fa-check-circle text-emerald-500 flex-shrink-0 text-base"></i>
                <div class="flex-1 truncate">
                    <span class="text-xs text-slate-400 font-normal mr-1">Adresse sélectionnée :</span>
                    <span id="map-chosen-text" class="font-semibold text-slate-700">—</span>
                </div>
                <span class="ml-auto text-xs font-mono text-teal-700 bg-teal-50 px-2 py-1 rounded border border-teal-200" id="map-chosen-coords"></span>
            </div>
        </div>

        {{-- ===== FORM + SIDEBAR GRID ===== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Main Form --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8">

                    <form method="POST"
                          id="address-form"
                          action="{{ $isEdit ? route('adresses.update', $adresse) : route('adresses.store') }}"
                          class="space-y-6">
                        @csrf
                        @if($isEdit) @method('put') @endif

                        {{-- Libellé --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="libelle" class="form-label mb-0">
                                    Nom / Libellé <span class="text-slate-400 font-normal">(optionnel)</span>
                                </label>
                                <span class="text-[11px] text-slate-400">ex: Maison, Bureau, Atelier</span>
                            </div>
                            <div class="input-wrap mb-2.5">
                                <i class="fas fa-tag input-icon"></i>
                                <input id="libelle" name="libelle" type="text"
                                       class="form-input {{ $errors->has('libelle') ? 'border-red-400 bg-red-50' : '' }}"
                                       value="{{ old('libelle', $adresse->libelle) }}"
                                       placeholder="ex. Domicile principal, Atelier textile…"
                                       maxlength="100">
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[11px] text-slate-400 mr-1">Suggestions :</span>
                                <button type="button" class="tag-preset-btn" onclick="setLibelle('Maison')">Maison</button>
                                <button type="button" class="tag-preset-btn" onclick="setLibelle('Travail')">Travail</button>
                                <button type="button" class="tag-preset-btn" onclick="setLibelle('Atelier')">Atelier</button>
                                <button type="button" class="tag-preset-btn" onclick="setLibelle('Dépôt')">Dépôt</button>
                                <button type="button" class="tag-preset-btn" onclick="setLibelle('Famille')">Famille</button>
                            </div>
                            @error('libelle')
                                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Rue --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="rue" class="form-label mb-0">
                                    Rue et numéro <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] text-teal-600 font-medium">
                                    <i class="fas fa-magic mr-1"></i> Rempli via la carte
                                </span>
                            </div>
                            <div class="input-wrap">
                                <i class="fas fa-road input-icon"></i>
                                <input id="rue" name="rue" type="text"
                                       class="form-input {{ $errors->has('rue') ? 'border-red-400 bg-red-50' : '' }}"
                                       value="{{ old('rue', $adresse->rue) }}"
                                       placeholder="ex. 15 Avenue Habib Bourguiba"
                                       required maxlength="255">
                            </div>
                            @error('rue')
                                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Ville + Code postal --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="ville" class="form-label">
                                    Ville <span class="text-red-500">*</span>
                                </label>
                                <div class="input-wrap">
                                    <i class="fas fa-city input-icon"></i>
                                    <input id="ville" name="ville" type="text"
                                           class="form-input {{ $errors->has('ville') ? 'border-red-400 bg-red-50' : '' }}"
                                           value="{{ old('ville', $adresse->ville) }}"
                                           placeholder="ex. Tunis, Sousse, Sfax…"
                                           required maxlength="100">
                                </div>
                                @error('ville')
                                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label for="code_postal" class="form-label">
                                    Code postal <span class="text-red-500">*</span>
                                </label>
                                <div class="input-wrap">
                                    <i class="fas fa-mail-bulk input-icon"></i>
                                    <input id="code_postal" name="code_postal" type="text"
                                           class="form-input {{ $errors->has('code_postal') ? 'border-red-400 bg-red-50' : '' }}"
                                           value="{{ old('code_postal', $adresse->code_postal) }}"
                                           placeholder="ex. 1000"
                                           required maxlength="20">
                                </div>
                                @error('code_postal')
                                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        {{-- GPS Coordinates --}}
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-crosshairs text-teal-600 text-sm"></i>
                                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Coordonnées GPS (Latitude / Longitude)
                                    </span>
                                </div>
                                <span class="text-[11px] text-teal-600 font-semibold flex items-center gap-1">
                                    <i class="fas fa-check-circle"></i> Synchronisé avec le marqueur
                                </span>
                            </div>

                            <p class="text-xs text-slate-500 mb-3">
                                Déplacez le marqueur sur la carte pour ajuster vos coordonnées avec précision.
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="latitude" class="text-[11px] font-medium text-slate-500 mb-1 block">Latitude</label>
                                    <div class="input-wrap">
                                        <i class="fas fa-arrows-alt-v input-icon"></i>
                                        <input id="latitude" name="latitude" type="number" step="0.00000001"
                                               class="form-input text-xs {{ $errors->has('latitude') ? 'border-red-400 bg-red-50' : '' }}"
                                               value="{{ old('latitude', $adresse->latitude) }}"
                                               placeholder="ex. 36.8065"
                                               readonly>
                                    </div>
                                    @error('latitude')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="longitude" class="text-[11px] font-medium text-slate-500 mb-1 block">Longitude</label>
                                    <div class="input-wrap">
                                        <i class="fas fa-arrows-alt-h input-icon"></i>
                                        <input id="longitude" name="longitude" type="number" step="0.00000001"
                                               class="form-input text-xs {{ $errors->has('longitude') ? 'border-red-400 bg-red-50' : '' }}"
                                               value="{{ old('longitude', $adresse->longitude) }}"
                                               placeholder="ex. 10.1815"
                                               readonly>
                                    </div>
                                    @error('longitude')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Adresse par défaut --}}
                        <div class="p-4 bg-emerald-50/60 border border-emerald-100 rounded-2xl flex items-start gap-3">
                            <input id="par_defaut" name="par_defaut" type="checkbox" value="1"
                                   class="mt-1 h-4 w-4 text-teal-600 rounded border-slate-300 focus:ring-teal-500 cursor-pointer"
                                   {{ old('par_defaut', $adresse->par_defaut) ? 'checked' : '' }}>
                            <label for="par_defaut" class="cursor-pointer">
                                <span class="font-semibold text-slate-800 text-sm block">
                                    Définir comme adresse principale par défaut
                                </span>
                                <span class="text-xs text-slate-500 block leading-relaxed">
                                    Cette adresse sera automatiquement sélectionnée lors de vos demandes de don ou de commande.
                                </span>
                            </label>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="pt-4 flex flex-col sm:flex-row items-center gap-3">
                            <button type="submit" class="btn-primary w-full sm:w-auto">
                                <i class="fas {{ $isEdit ? 'fa-check' : 'fa-save' }}"></i>
                                <span>{{ $isEdit ? 'Mettre à jour l\'adresse' : 'Enregistrer l\'adresse' }}</span>
                            </button>
                            <a href="{{ route('adresses.index') }}"
                               class="w-full sm:w-auto text-center px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors"
                                style="text-decoration:none">
                                Annuler
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            {{-- ===== SIDEBAR ===== --}}
            <div class="space-y-6">

                {{-- Guide --}}
                <div class="bg-white rounded-2xl border border-teal-100 p-5" style="background: linear-gradient(135deg,#f0fdf9,#f8fafc);">
                    <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center mb-3">
                        <i class="fas fa-map text-lg"></i>
                    </div>
                    <h4 class="font-display font-bold text-slate-800 text-sm mb-2">Comment choisir son adresse ?</h4>
                    <ol class="space-y-2.5 text-xs text-slate-600">
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-[10px]">1</span>
                            <span><strong>Recherchez</strong> un lieu dans la barre de recherche sur la carte ou naviguez manuellement.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-[10px]">2</span>
                            <span><strong>Cliquez sur la carte</strong> ou glissez l'épingle pour pointer l'emplacement exact.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-[10px]">3</span>
                            <span>La rue, la ville, le code postal et les coordonnées GPS sont <strong>remplis en temps réel</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-[10px]">4</span>
                            <span>Vous pouvez retoucher manuellement les textes avant d'enregistrer.</span>
                        </li>
                    </ol>
                </div>

                {{-- Privacy info --}}
                <div class="info-box">
                    <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center mb-3">
                        <i class="fas fa-shield-alt text-lg"></i>
                    </div>
                    <h4 class="font-display font-bold text-slate-800 text-sm mb-1.5">Confidentialité & Sécurité</h4>
                    <p class="text-xs text-slate-500 leading-relaxed mb-3">
                        Vos adresses sont protégées et ne sont utilisées que pour organiser les collectes et livraisons avec vos autorisations.
                    </p>
                    <div class="space-y-2 text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-teal-500"></i>
                            <span>Rattachée uniquement à votre compte</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-teal-500"></i>
                            <span>Modifiable & supprimable à tout moment</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-teal-500"></i>
                            <span>Itinéraires optimisés pour les collecteurs</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    /* ========================================================
       UTILITY: set libellé quick-presets
    ======================================================== */
    function setLibelle(text) {
        const input = document.getElementById('libelle');
        input.value = text;
        input.focus();
    }

    function setField(id, value, highlight) {
        const el = document.getElementById(id);
        if (!el || value === undefined || value === null) return;
        el.value = value;
        if (highlight) {
            el.classList.remove('autofilled');
            void el.offsetWidth; // trigger reflow
            el.classList.add('autofilled');
            setTimeout(() => el.classList.remove('autofilled'), 2000);
        }
    }

    function setGPSFields(lat, lng) {
        const latVal = typeof lat === 'number' ? lat : parseFloat(lat);
        const lngVal = typeof lng === 'number' ? lng : parseFloat(lng);
        setField('latitude',  latVal.toFixed(7), false);
        setField('longitude', lngVal.toFixed(7), false);
    }

    function updateChosenStrip(addressText, lat, lng) {
        const strip = document.getElementById('map-chosen-strip');
        const textEl = document.getElementById('map-chosen-text');
        const coordsEl = document.getElementById('map-chosen-coords');
        if (!strip) return;
        textEl.textContent = addressText || 'Emplacement sélectionné';
        const latVal = typeof lat === 'number' ? lat : parseFloat(lat);
        const lngVal = typeof lng === 'number' ? lng : parseFloat(lng);
        coordsEl.textContent = latVal.toFixed(5) + ', ' + lngVal.toFixed(5);
        strip.classList.add('visible');
    }

    const INIT_LAT  = {{ $initLat }};
    const INIT_LNG  = {{ $initLng }};
    const INIT_ZOOM = {{ $initZoom }};
</script>

@if($mapsReady)
{{-- ========================================================
     GOOGLE MAPS IMPLEMENTATION
======================================================== --}}
<script>
    let map, marker, geocoder, autocomplete;

    function initMap() {
        geocoder = new google.maps.Geocoder();

        map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: INIT_LAT, lng: INIT_LNG },
            zoom:   INIT_ZOOM,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            styles: [
                { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] },
                { featureType: 'transit', stylers: [{ visibility: 'simplified' }] }
            ]
        });

        // Place existing marker if editing or coords exist
        @if($adresse->latitude && $adresse->longitude)
            placeMarker({ lat: {{ $adresse->latitude }}, lng: {{ $adresse->longitude }} });
            updateChosenStrip("{{ addslashes($adresse->rue . ', ' . $adresse->ville) }}", {{ $adresse->latitude }}, {{ $adresse->longitude }});
        @endif

        // Places Autocomplete on map search box
        autocomplete = new google.maps.places.Autocomplete(
            document.getElementById('map-search'),
            { fields: ['address_components', 'geometry', 'name', 'formatted_address'] }
        );
        autocomplete.bindTo('bounds', map);

        autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();
            if (!place.geometry || !place.geometry.location) return;

            map.setCenter(place.geometry.location);
            map.setZoom(16);
            placeMarker(place.geometry.location);
            fillFromGoogleResults([{ address_components: place.address_components, formatted_address: place.formatted_address, name: place.name }], place.geometry.location);
        });

        // Click on map → zoom in to street level & reverse geocode
        map.addListener('click', function (e) {
            if (map.getZoom() < 15) {
                map.setZoom(16);
                map.panTo(e.latLng);
            }
            placeMarker(e.latLng);
            reverseGeocodeGoogle(e.latLng);
        });

        // Hide loading
        document.getElementById('map-loading').style.display = 'none';
    }

    function placeMarker(location) {
        if (marker) {
            marker.setPosition(location);
        } else {
            marker = new google.maps.Marker({
                position: location,
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 11,
                    fillColor: '#0d9488',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 3,
                }
            });

            marker.addListener('dragend', function () {
                reverseGeocodeGoogle(marker.getPosition());
            });
        }

        const lat = typeof location.lat === 'function' ? location.lat() : location.lat;
        const lng = typeof location.lng === 'function' ? location.lng() : location.lng;
        setGPSFields(lat, lng);
    }

    function reverseGeocodeGoogle(latLng) {
        geocoder.geocode({ location: latLng }, function (results, status) {
            if (status === 'OK' && results && results.length > 0) {
                fillFromGoogleResults(results, latLng);
            }
        });
    }

    function fillFromGoogleResults(results, latLng) {
        if (!results || results.length === 0) return;

        let streetNumber = '';
        let route = '';
        let locality = '';
        let admin2 = '';
        let admin1 = '';
        let postalCode = '';
        let premise = '';
        let poi = '';
        let neighborhood = '';

        for (let r of results) {
            if (!r.address_components) continue;
            for (let c of r.address_components) {
                if (c.types.includes('street_number') && !streetNumber) streetNumber = c.long_name;
                if (c.types.includes('route') && !route) route = c.long_name;
                if (c.types.includes('premise') && !premise) premise = c.long_name;
                if (c.types.includes('point_of_interest') && !poi) poi = c.long_name;
                if (c.types.includes('neighborhood') && !neighborhood) neighborhood = c.long_name;
                if (c.types.includes('locality') && !locality) locality = c.long_name;
                if (c.types.includes('administrative_area_level_2') && !admin2) admin2 = c.long_name;
                if (c.types.includes('administrative_area_level_1') && !admin1) admin1 = c.long_name;
                if (c.types.includes('postal_code') && !postalCode) postalCode = c.long_name;
            }
        }

        const city = locality || admin2 || admin1 || '';
        let street = '';

        if (route) {
            street = streetNumber ? `${streetNumber} ${route}` : route;
            if (poi && poi !== route && poi !== city) {
                street = `${poi}, ${street}`;
            }
        } else if (poi || premise) {
            street = (poi || premise) + (neighborhood ? `, ${neighborhood}` : '');
        } else if (neighborhood) {
            street = neighborhood;
        } else if (results[0] && results[0].formatted_address) {
            const parts = results[0].formatted_address.split(',').map(s => s.trim());
            const filtered = parts.filter(p => p !== city && !/tunisie|tunisia/i.test(p) && p !== postalCode);
            street = filtered[0] || parts[0];
        }

        if (!street) {
            street = city ? ('Centre ' + city) : 'Zone Localisée';
        }

        const lat = typeof latLng.lat === 'function' ? latLng.lat() : latLng.lat;
        const lng = typeof latLng.lng === 'function' ? latLng.lng() : latLng.lng;

        setField('rue', street, true);
        if (city) setField('ville', city, true);
        if (postalCode) setField('code_postal', postalCode, true);
        setGPSFields(lat, lng);

        const formatted = results[0]?.formatted_address || `${street}, ${city}`;
        updateChosenStrip(formatted, lat, lng);
    }

    function recenterMap() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(function (pos) {
            const loc = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            map.setCenter(loc);
            map.setZoom(16);
            placeMarker(loc);
            reverseGeocodeGoogle(new google.maps.LatLng(loc.lat, loc.lng));
        });
    }
</script>
<script
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=initMap&loading=async"
    async defer>
</script>

@else
{{-- ========================================================
     OPENSTREETMAP / LEAFLET IMPLEMENTATION (Zero-config Fallback)
======================================================== --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let map, marker;
    let searchTimeout = null;

    document.addEventListener('DOMContentLoaded', function () {
        initLeafletMap();
        initLeafletSearch();
    });

    function initLeafletMap() {
        map = L.map('map', {
            center: [INIT_LAT, INIT_LNG],
            zoom: INIT_ZOOM,
            zoomControl: true
        });

        // OpenStreetMap Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Place existing marker if editing or coords exist
        @if($adresse->latitude && $adresse->longitude)
            placeLeafletMarker({{ $adresse->latitude }}, {{ $adresse->longitude }});
            updateChosenStrip("{{ addslashes($adresse->rue . ', ' . $adresse->ville) }}", {{ $adresse->latitude }}, {{ $adresse->longitude }});
        @endif

        // Click on map: zoom in to street level if zoomed out, place pin and reverse geocode
        map.on('click', function (e) {
            if (map.getZoom() < 15) {
                map.setView([e.latlng.lat, e.latlng.lng], 16, { animate: true });
            }
            placeLeafletMarker(e.latlng.lat, e.latlng.lng);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });

        // Hide loading
        document.getElementById('map-loading').style.display = 'none';
    }

    function placeLeafletMarker(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            const pinIcon = L.divIcon({
                className: 'custom-map-pin',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
            marker = L.marker([lat, lng], { draggable: true, icon: pinIcon }).addTo(map);

            marker.on('dragend', function (e) {
                const pos = marker.getLatLng();
                reverseGeocode(pos.lat, pos.lng);
            });
        }

        setGPSFields(lat, lng);
    }

    /**
     * Parse Nominatim data and fill all address fields (Rue + Numéro, Ville, Code Postal, GPS)
     */
    function fillFromAddressData(data, lat, lng) {
        if (!data) return;
        const a = data.address || {};

        // 1. Numéro de rue
        const houseNumber = (a.house_number || a.housenumber || a.street_number || '').trim();

        // 2. Nom de rue / route
        const roadCandidates = [
            a.road,
            a.pedestrian,
            a.residential,
            a.street,
            a.avenue,
            a.boulevard,
            a.alley,
            a.path,
            a.footway,
            a.cycleway,
            a.highway,
            a.square,
            a.plaza
        ];
        const road = roadCandidates.find(Boolean) || '';

        // 3. Point d'intérêt / Bâtiment / Résidence
        const poiCandidates = [
            data.name,
            a.amenity,
            a.building,
            a.shop,
            a.office,
            a.tourism,
            a.commercial,
            a.industrial
        ];
        let poi = poiCandidates.find(Boolean) || '';

        // 4. Quartier / Secteur
        const districtCandidates = [
            a.suburb,
            a.neighbourhood,
            a.quarter,
            a.city_district,
            a.district,
            a.hamlet
        ];
        let district = districtCandidates.find(Boolean) || '';

        // 5. Ville
        const cityCandidates = [
            a.city,
            a.town,
            a.village,
            a.municipality,
            a.county,
            a.state_district,
            a.state
        ];
        const city = cityCandidates.find(Boolean) || '';

        // 6. Code postal
        const postalCode = (a.postcode || a.postal_code || '').trim();

        // Éviter les doublons POI == road ou POI == city
        if (poi && (poi === road || poi === city)) poi = '';
        if (district && (district === road || district === city)) district = '';

        // Construction précise du champ "Rue et numéro"
        let street = '';

        if (road) {
            street = houseNumber ? `${houseNumber} ${road}` : road;
            if (poi && !road.includes(poi)) {
                street = `${poi}, ${street}`;
            }
        } else if (poi) {
            street = houseNumber ? `${houseNumber} ${poi}` : poi;
            if (district) {
                street = `${street}, ${district}`;
            }
        } else if (district) {
            street = houseNumber ? `${houseNumber} ${district}` : district;
        } else if (data.display_name) {
            const parts = data.display_name.split(',').map(s => s.trim());
            const filtered = parts.filter(p =>
                p &&
                p !== city &&
                p !== a.state &&
                p !== a.country &&
                p !== a.postcode &&
                !/^gouvernorat/i.test(p) &&
                !/^délégation/i.test(p) &&
                !/^tunisie$/i.test(p) &&
                !/^tunisia$/i.test(p)
            );
            street = filtered.slice(0, 2).join(', ');
            if (!street) {
                street = parts[0] || '';
            }
        }

        // Garantie absolue : la rue n'est jamais vide
        if (!street) {
            street = city ? ('Centre ' + city) : 'Zone Localisée';
        }

        // Remplissage avec animation visuelle
        setField('rue', street, true);
        if (city) setField('ville', city, true);
        if (postalCode) setField('code_postal', postalCode, true);
        setGPSFields(lat, lng);

        const summary = (houseNumber && road)
            ? `${houseNumber} ${road}, ${city}`
            : (data.display_name ? data.display_name.split(',').slice(0, 3).join(', ') : `${street}, ${city}`);

        updateChosenStrip(summary, lat, lng);
    }

    /**
     * Géocodage inversé haute précision (avec fallback proxy Laravel intégré)
     */
    function reverseGeocode(lat, lng) {
        const osmUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
        const proxyUrl = `{{ route('adresses.reverse-geocode') }}?lat=${lat}&lng=${lng}`;

        // Requête directe avec timeout de 3.5s
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3500);

        fetch(osmUrl, {
            headers: { 'Accept-Language': 'fr' },
            signal: controller.signal
        })
        .then(res => {
            clearTimeout(timeoutId);
            if (!res.ok) throw new Error('OSM error: ' + res.status);
            return res.json();
        })
        .then(data => {
            fillFromAddressData(data, lat, lng);
        })
        .catch(() => {
            // En cas d'échec client (CORS, blocage, timeout), appel transparent au proxy serveur Laravel
            fetch(proxyUrl)
                .then(res => res.json())
                .then(data => {
                    if (data && !data.error) {
                        fillFromAddressData(data, lat, lng);
                    } else {
                        setGPSFields(lat, lng);
                    }
                })
                .catch(() => {
                    setGPSFields(lat, lng);
                });
        });
    }

    /**
     * Autocomplétion de recherche dans la barre de carte
     */
    function initLeafletSearch() {
        const searchInput = document.getElementById('map-search');
        const resultsBox = document.getElementById('map-search-results');

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            if (query.length < 3) {
                resultsBox.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5`;
                fetch(url, { headers: { 'Accept-Language': 'fr' } })
                    .then(res => res.json())
                    .then(items => {
                        resultsBox.innerHTML = '';
                        if (!items || items.length === 0) {
                            resultsBox.style.display = 'none';
                            return;
                        }

                        items.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'search-result-item';
                            div.innerHTML = `<i class="fas fa-map-marker-alt text-teal-500"></i> <span class="truncate">${item.display_name}</span>`;
                            div.addEventListener('click', () => {
                                const lat = parseFloat(item.lat);
                                const lon = parseFloat(item.lon);
                                map.setView([lat, lon], 16);
                                placeLeafletMarker(lat, lon);
                                fillFromAddressData(item, lat, lon);
                                searchInput.value = item.display_name.split(',').slice(0, 2).join(',');
                                resultsBox.style.display = 'none';
                            });
                            resultsBox.appendChild(div);
                        });
                        resultsBox.style.display = 'block';
                    })
                    .catch(() => {
                        resultsBox.style.display = 'none';
                    });
            }, 350);
        });

        // Touche Entrée = sélection du premier résultat
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstItem = resultsBox.querySelector('.search-result-item');
                if (firstItem) firstItem.click();
            }
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                resultsBox.style.display = 'none';
            }
        });
    }

    function recenterMap() {
        if (!navigator.geolocation) {
            alert("La géolocalisation n'est pas supportée par votre navigateur.");
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            map.setView([lat, lng], 16);
            placeLeafletMarker(lat, lng);
            reverseGeocode(lat, lng);
        }, function (err) {
            alert("Impossible d'obtenir votre position : " + err.message);
        }, { enableHighAccuracy: true });
    }
</script>
@endif

@endsection
