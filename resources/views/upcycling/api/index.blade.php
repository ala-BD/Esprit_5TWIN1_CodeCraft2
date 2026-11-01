@extends('upcycling.layouts.upcycling')

@section('title', 'Accès API')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Accès API</span>
@endsection

@php
    $base = url('/api/v1');
    $couleurs = ['GET' => 'bg-sky-100 text-sky-700', 'POST' => 'bg-emerald-100 text-emerald-700',
                 'PATCH' => 'bg-amber-100 text-amber-700', 'DELETE' => 'bg-red-100 text-red-700'];
@endphp

@section('upcycling-content')

<div class="mb-8">
    <h1 class="font-display text-3xl font-extrabold text-gray-900">API REST</h1>
    <p class="text-gray-500 mt-1">Accédez au module Upcycling depuis une application mobile, Postman ou un script.
        Authentification par token <strong>Sanctum</strong>, réponses JSON uniformes (<strong>API Resources</strong>),
        validation par <strong>Form Requests</strong> et <strong>limitation du nombre d'appels</strong>.</p>
</div>

{{-- Bonnes pratiques --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    @foreach([
        ['fa-key', 'Tokens Sanctum', 'Permissions par token : lecture, écriture, IA. Révocables à tout moment.'],
        ['fa-code', 'API Resources', 'JSON filtré : jamais de mot de passe, d\'embedding ni d\'e-mail de tiers.'],
        ['fa-gauge-high', 'Rate limiting', '60 req/min, 5 connexions/min, 10 appels IA/min (réponse 429).'],
        ['fa-check-double', 'Form Requests', 'Validation isolée, messages en français, contrôleurs légers.'],
    ] as [$icone, $titre, $texte])
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5">
            <div class="w-10 h-10 rounded-xl bg-navy-DEFAULT text-white flex items-center justify-center mb-3"><i class="fas {{ $icone }}"></i></div>
            <p class="font-semibold text-gray-900 text-sm">{{ $titre }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $texte }}</p>
        </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-5 gap-6 mb-8">

    {{-- ===== Créer un token ===== --}}
    <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-display font-bold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-plus-circle text-primary"></i> Nouveau token</h2>

        @if(session('nouveau_token'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 mb-5">
                <p class="text-xs font-bold text-emerald-700 mb-2"><i class="fas fa-triangle-exclamation"></i> Copiez ce token maintenant, il ne sera plus affiché :</p>
                <div class="flex gap-2">
                    <input id="token-clair" readonly value="{{ session('nouveau_token') }}"
                           class="flex-1 min-w-0 font-mono text-xs px-3 py-2 rounded-lg border border-emerald-200 bg-white">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('token-clair').value); this.innerHTML='<i class=\'fas fa-check\'></i>';"
                            class="px-3 rounded-lg bg-emerald-600 text-white text-xs font-bold"><i class="fas fa-copy"></i></button>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('upcycling.api.tokens.store') }}" class="space-y-4" novalidate>
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="nom" value="{{ old('nom') }}" placeholder="Postman, application mobile…"
                       class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('nom') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                @error('nom')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <p class="block text-sm font-semibold text-gray-700 mb-1.5">Permissions <span class="text-red-500">*</span></p>
                <div class="space-y-2">
                    @foreach($abilities as $code => $description)
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-100 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="abilities[]" value="{{ $code }}" class="mt-0.5 rounded text-primary focus:ring-primary"
                                   @checked(in_array($code, old('abilities', array_keys($abilities))))>
                            <span><span class="font-mono text-xs font-bold text-gray-800">{{ $code }}</span>
                                <span class="block text-xs text-gray-500">{{ $description }}</span></span>
                        </label>
                    @endforeach
                </div>
                @error('abilities')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Validité</label>
                <select name="expiration" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                    @foreach($expirations as $jours => $libelle)
                        <option value="{{ $jours }}" @selected(old('expiration', '30') == $jours)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-primary w-full !py-3 !rounded-2xl"><i class="fas fa-key"></i> Générer le token</button>
        </form>
    </div>

    {{-- ===== Mes tokens ===== --}}
    <div class="lg:col-span-3 bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-display font-bold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-key text-primary"></i> Mes tokens ({{ $tokens->count() }})</h2>

        <div class="space-y-3">
            @forelse($tokens as $token)
                @php $expire = $token->expires_at?->isPast(); @endphp
                <div class="rounded-2xl border border-gray-100 p-4 flex flex-col sm:flex-row sm:items-center gap-3 {{ $expire ? 'opacity-60' : '' }}">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900">{{ $token->name }}
                            @if($expire)<span class="ml-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-200 text-gray-600">Expiré</span>@endif
                        </p>
                        <div class="flex flex-wrap gap-1 mt-1.5">
                            @foreach($token->abilities as $ability)
                                <span class="font-mono text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600">{{ $ability }}</span>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">
                            Créé {{ $token->created_at->diffForHumans() }}
                            · {{ $token->last_used_at ? 'utilisé ' . $token->last_used_at->diffForHumans() : 'jamais utilisé' }}
                            · {{ $token->expires_at ? 'expire le ' . $token->expires_at->format('d/m/Y') : 'sans expiration' }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('upcycling.api.tokens.destroy', $token->id) }}" onsubmit="return confirm('Révoquer ce token ?')">
                        @csrf @method('DELETE')
                        <button class="text-xs font-bold px-3 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-red-600"><i class="fas fa-ban"></i> Révoquer</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-10">Aucun token. Créez-en un pour appeler l'API.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ===== Démarrage rapide ===== --}}
<div class="bg-navy-dark rounded-3xl p-6 mb-8 text-white">
    <h2 class="font-display font-bold mb-4"><i class="fas fa-terminal text-secondary mr-2"></i> Démarrage rapide</h2>
<pre class="text-xs leading-relaxed overflow-x-auto text-slate-200"><span class="text-slate-400"># 1. Obtenir un token (ou en créer un ci-dessus)</span>
curl -X POST {{ $base }}/auth/token \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"{{ Auth::user()->email }}","password":"...","nom_appareil":"terminal"}'

<span class="text-slate-400"># 2. Lister ses projets</span>
curl {{ $base }}/projets -H "Accept: application/json" -H "Authorization: Bearer VOTRE_TOKEN"

<span class="text-slate-400"># 3. Poser une question au styliste IA</span>
curl -X POST {{ $base }}/projets/1/styliste -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" -d '{"contenu":"Quelle doublure choisir ?"}'</pre>
</div>

{{-- ===== Endpoints ===== --}}
<div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-display font-bold text-gray-900"><i class="fas fa-book text-primary mr-2"></i> Endpoints</h2>
        <span class="font-mono text-xs text-gray-500">{{ $base }}</span>
    </div>
    @foreach($endpoints as $groupe => $routes)
        <div class="px-6 pt-5 pb-2">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">{{ $groupe }}</p>
            <div class="divide-y divide-gray-50">
                @foreach($routes as [$methode, $chemin, $permission, $description])
                    <div class="py-2.5 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
                        <span class="w-16 text-center font-mono text-[11px] font-bold px-2 py-1 rounded {{ $couleurs[$methode] }}">{{ $methode }}</span>
                        <code class="md:w-72 text-xs text-gray-800">{{ $chemin }}</code>
                        <span class="flex-1 text-sm text-gray-600">{{ $description }}</span>
                        @if($permission)
                            <span class="font-mono text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600">{{ $permission }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
    <div class="px-6 py-4 mt-3 bg-slate-50 text-xs text-gray-500">
        Codes de réponse : <strong>200/201</strong> succès · <strong>204</strong> suppression · <strong>401</strong> token absent ou invalide ·
        <strong>403</strong> permission ou accès refusé · <strong>404</strong> introuvable · <strong>422</strong> validation ou règle métier ·
        <strong>429</strong> trop de requêtes (en-tête Retry-After).
    </div>
</div>

@endsection
