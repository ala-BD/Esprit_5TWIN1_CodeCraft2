{{-- Projet terminé : contrôle qualité IA (avant / après) + revente sur la marketplace (M2) --}}
@php $controle = $projet->controle_qualite_ia; @endphp

<div class="grid lg:grid-cols-3 gap-6 mb-6">

    {{-- ===== Contrôle qualité IA ===== --}}
    <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between gap-3 mb-5">
            <h2 class="font-display font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-clipboard-check text-violet-500"></i> Contrôle qualité IA
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-gradient-to-r from-violet-600 to-pink-500 text-white">
                    <i class="fas fa-wand-magic-sparkles"></i> Vision
                </span>
            </h2>
            @if($estAtelierProjet && $projet->photo_resultat)
                <form method="POST" action="{{ route('upcycling.projets.controle-qualite', $projet) }}"
                      onsubmit="const b=this.querySelector('button'); b.disabled=true; b.innerHTML='<i class=\'fas fa-circle-notch fa-spin\'></i> Analyse…';">
                    @csrf
                    <button class="text-xs font-semibold text-violet-600 bg-violet-50 hover:bg-violet-100 px-3 py-2 rounded-lg transition-colors disabled:opacity-60">
                        <i class="fas fa-rotate"></i> {{ $controle ? 'Relancer' : 'Lancer le contrôle' }}
                    </button>
                </form>
            @endif
        </div>

        @if($controle)
            <div class="flex flex-col sm:flex-row gap-6">
                {{-- Score --}}
                <div class="flex sm:flex-col items-center gap-3 flex-shrink-0">
                    <div class="relative w-24 h-24">
                        <svg viewBox="0 0 36 36" class="w-24 h-24 -rotate-90">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3"></circle>
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="{{ $controle['score'] >= 75 ? '#10b981' : ($controle['score'] >= 50 ? '#f59e0b' : '#ef4444') }}"
                                    stroke-width="3" stroke-dasharray="{{ $controle['score'] }} 100" stroke-linecap="round"></circle>
                        </svg>
                        <span class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-extrabold text-gray-900 leading-none">{{ $controle['score'] }}</span>
                            <span class="text-[9px] text-gray-400">/ 100</span>
                        </span>
                    </div>
                    <span class="text-xs font-bold px-3 py-1 rounded-full {{ $controle['conforme'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        <i class="fas {{ $controle['conforme'] ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                        {{ $controle['conforme'] ? 'Conforme' : 'À vérifier' }}
                    </span>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900">{{ $controle['verdict'] }}</p>

                    <div class="grid sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 mb-2">Points forts</p>
                            <ul class="space-y-1.5">
                                @foreach($controle['points_forts'] as $point)
                                    <li class="flex gap-2 text-sm text-gray-600"><i class="fas fa-check text-emerald-500 mt-1 text-xs"></i> {{ $point }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-amber-600 mb-2">Améliorations</p>
                            <ul class="space-y-1.5">
                                @forelse($controle['ameliorations'] as $point)
                                    <li class="flex gap-2 text-sm text-gray-600"><i class="fas fa-arrow-up text-amber-500 mt-1 text-xs"></i> {{ $point }}</li>
                                @empty
                                    <li class="text-sm text-gray-400">Aucune remarque.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    @if($controle['description_portfolio'])
                        <p class="mt-4 text-sm text-gray-600 italic bg-slate-50 rounded-2xl p-4">
                            <i class="fas fa-quote-left text-gray-300 mr-1"></i> {{ $controle['description_portfolio'] }}
                        </p>
                    @endif
                    <p class="text-[11px] text-gray-400 mt-3">Analyse du {{ \Illuminate\Support\Carbon::parse($controle['date'])->format('d/m/Y à H:i') }} — photo d'origine, photo du produit fini et idée retenue comparées par Gemini.</p>
                </div>
            </div>
        @elseif(!$projet->photo_resultat)
            <p class="text-sm text-gray-500">Pas de photo du produit fini : le contrôle qualité compare la photo d'origine à celle du résultat.</p>
        @else
            <p class="text-sm text-gray-500">
                L'IA n'a pas encore comparé le produit fini à la commande.
                {{ $estAtelierProjet ? 'Lancez le contrôle pour obtenir un score de conformité et une description pour votre portfolio.' : '' }}
            </p>
        @endif
    </div>

    {{-- ===== Revente sur la marketplace (M2) ===== --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-display font-bold text-gray-900 flex items-center gap-2 mb-1">
            <i class="fas fa-store text-primary"></i> Marketplace
        </h2>

        @if($projet->article)
            <p class="text-sm text-gray-500 mb-4">Ce produit est en vente sur la marketplace RETISS.</p>
            <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                <p class="font-semibold text-gray-900">{{ $projet->article->titre }}</p>
                <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ number_format($projet->article->prix, 2, ',', ' ') }} <span class="text-sm">DT</span></p>
                <p class="text-xs text-gray-500 mt-1">Statut : {{ ucfirst(strtolower($projet->article->statut)) }}</p>
            </div>
            <a href="{{ route('articles.show', $projet->article) }}" class="btn-primary w-full mt-4 !py-3 !rounded-2xl">
                <i class="fas fa-arrow-up-right-from-square"></i> Voir l'annonce
            </a>
        @elseif($estProprietaire && $projet->estPubliable())
            <p class="text-sm text-gray-500 mb-4">Vous ne comptez pas le garder ? Revendez cette pièce unique : elle continuera de circuler.</p>
            <form method="POST" action="{{ route('upcycling.projets.publier', $projet) }}" class="space-y-3" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Prix de vente (DT) <span class="text-red-500">*</span></label>
                    <input type="number" name="prix" step="0.5" min="1" value="{{ old('prix', $prixSuggere) }}"
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('prix') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                    <p class="text-[11px] text-gray-400 mt-1">Suggestion : coût de la transformation + 30 %.</p>
                    @error('prix')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <textarea name="description" rows="3" placeholder="Description (laissée vide : rédigée à partir du contrôle qualité IA)"
                          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm resize-none">{{ old('description') }}</textarea>
                <button class="btn-primary w-full !py-3 !rounded-2xl"><i class="fas fa-tag"></i> Mettre en vente</button>
            </form>
        @elseif(!$projet->photo_resultat)
            <p class="text-sm text-gray-500">La revente nécessite la photo du produit fini.</p>
        @else
            <p class="text-sm text-gray-500">Le client peut revendre ce produit sur la marketplace.</p>
        @endif
    </div>
</div>
