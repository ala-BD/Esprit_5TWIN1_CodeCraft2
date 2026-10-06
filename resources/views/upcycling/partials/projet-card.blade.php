{{-- Carte d'un projet d'upcycling (liste + dashboard). Paramètre : $projet --}}
@php
    $badge      = $projet->statut_badge;
    $estAtelier = Auth::user()->role === 'ATELIER';
    $estClient  = $projet->client_id === Auth::id();
@endphp

<div class="group bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">

    {{-- Photo --}}
    <a href="{{ route('upcycling.projets.show', $projet) }}" class="relative block aspect-[4/3] bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
        <div class="absolute inset-0 p-5 bg-gradient-to-br from-slate-50 to-slate-100 transition-transform duration-500 group-hover:scale-105">
            @include('upcycling.partials.photo', [
                'url'   => $projet->photo_resultat_url ?? $projet->photo_url,
                'alt'   => $projet->type_vetement,
                'icone' => \App\Models\Atelier::ICONS[$projet->categorie_produit] ?? 'fa-tshirt',
            ])
        </div>
        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-[11px] font-bold shadow-sm {{ $badge['class'] }}">{{ $badge['label'] }}</span>
        @if($projet->source_ia === 'GEMINI')
            <span class="absolute top-3 right-3 px-2 py-1 rounded-lg text-[10px] font-bold bg-white/90 text-violet-600 shadow-sm">
                <i class="fas fa-wand-magic-sparkles"></i> IA
            </span>
        @endif
    </a>

    {{-- Contenu --}}
    <div class="p-5 flex-1 flex flex-col">
        <a href="{{ route('upcycling.projets.show', $projet) }}" class="font-display font-bold text-gray-900 leading-snug hover:text-primary transition-colors line-clamp-1">
            {{ $projet->produit_final ?? 'Idée à choisir' }}
        </a>
        <p class="text-xs text-gray-500 mt-1 line-clamp-1">
            {{ $projet->type_vetement }} · {{ $projet->matiere }}{{ $projet->couleur ? ' · ' . $projet->couleur : '' }}
        </p>

        <div class="flex items-center gap-2 mt-3 text-xs text-gray-500">
            @if($estAtelier)
                <i class="fas fa-user text-gray-400"></i> {{ $projet->client->full_name }}
            @elseif($projet->atelier)
                <i class="fas fa-store text-gray-400"></i> {{ $projet->atelier->nom }}
            @else
                <i class="fas fa-store-slash text-gray-300"></i> <span class="text-gray-400">Aucun atelier</span>
            @endif
        </div>

        <div class="mt-auto pt-4">
            <div class="flex justify-between text-[11px] text-gray-400 mb-1">
                <span>Progression</span><span class="font-semibold text-gray-600">{{ $projet->progression }}%</span>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-primary to-accent" style="width: {{ $projet->progression }}%"></div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    @if($estClient && ($projet->estModifiable() || in_array($projet->statut, ['DEMANDE', 'ANNULE'])))
        <div class="px-5 pb-4 flex gap-2">
            <a href="{{ route('upcycling.projets.show', $projet) }}"
               class="flex-1 text-center text-xs font-semibold py-2 rounded-xl bg-gray-50 hover:bg-primary hover:text-white text-gray-600 transition-colors">
                <i class="fas fa-eye mr-1"></i> Voir
            </a>
            @if($projet->estModifiable())
                <a href="{{ route('upcycling.projets.edit', $projet) }}" title="Modifier"
                   class="w-9 flex items-center justify-center rounded-xl bg-amber-50 hover:bg-amber-500 hover:text-white text-amber-600 transition-colors">
                    <i class="fas fa-pen text-xs"></i>
                </a>
            @endif
            @if(in_array($projet->statut, ['DEMANDE', 'ANNULE']))
                <form method="POST" action="{{ route('upcycling.projets.destroy', $projet) }}"
                      onsubmit="return confirm('Supprimer définitivement cette demande ?')">
                    @csrf @method('DELETE')
                    <button type="submit" title="Supprimer"
                            class="w-9 h-full flex items-center justify-center rounded-xl bg-red-50 hover:bg-red-500 hover:text-white text-red-500 transition-colors">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
