{{-- Carte du tableau de production (Kanban). Paramètre : $projet (client et devis chargés) --}}
@php
    $suivante    = $projet->etape_suivante;
    $deplacable  = $suivante !== null;
    $devis       = $projet->devis_accepte;
    $livraison   = $projet->date_livraison_prevue;
@endphp

<article class="kanban-carte bg-white rounded-2xl border {{ $projet->en_retard ? 'border-red-200' : 'border-gray-100' }} shadow-sm hover:shadow-md transition-shadow p-3 {{ $deplacable ? 'carte-deplacable' : '' }}"
         data-id="{{ $projet->id }}"
         data-suivante="{{ $suivante }}"
         data-titre="{{ $projet->produit_final ?? $projet->type_vetement }}"
         data-url-avancer="{{ route('upcycling.projets.avancer', $projet) }}">
    <div class="flex gap-3">
        <a href="{{ route('upcycling.projets.show', $projet) }}" class="w-14 h-14 rounded-xl bg-gradient-to-br from-slate-50 to-slate-100 p-1.5 flex-shrink-0" draggable="false">
            @include('upcycling.partials.photo', [
                'url'   => $projet->photo_resultat_url ?? $projet->photo_url,
                'alt'   => $projet->type_vetement,
                'icone' => \App\Models\Atelier::ICONS[$projet->categorie_produit] ?? 'fa-tshirt',
            ])
        </a>
        <div class="min-w-0 flex-1">
            <a href="{{ route('upcycling.projets.show', $projet) }}" class="block text-sm font-bold text-gray-900 leading-snug line-clamp-2 hover:text-primary" draggable="false">
                {{ $projet->produit_final ?? 'Idée à choisir' }}
            </a>
            <p class="text-[11px] text-gray-500 mt-0.5 truncate"><i class="fas fa-user text-gray-300"></i> {{ $projet->client->full_name }}</p>
        </div>
        @if($deplacable)
            <span class="text-gray-300 text-xs pt-1" title="Glisser vers l'étape suivante"><i class="fas fa-grip-vertical"></i></span>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-1.5 mt-3 text-[10px] font-semibold">
        @if($devis)
            <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700">{{ number_format($devis->montant, 0) }} DT</span>
        @endif
        @if($projet->en_retard)
            <span class="px-2 py-0.5 rounded-md bg-red-500 text-white"><i class="fas fa-triangle-exclamation"></i> En retard</span>
        @elseif($livraison && $projet->statut !== 'TERMINE')
            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600"><i class="far fa-calendar"></i> {{ $livraison->translatedFormat('d M') }}</span>
        @endif
        @if($projet->statut === 'TERMINE' && $projet->controle_qualite_ia)
            <span class="px-2 py-0.5 rounded-md bg-violet-50 text-violet-700"><i class="fas fa-wand-magic-sparkles"></i> QC {{ $projet->controle_qualite_ia['score'] }}/100</span>
        @endif
        @if($projet->statut === 'ATELIER_CHOISI')
            @if($projet->devis_en_attente)
                <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700"><i class="fas fa-hourglass-half"></i> Devis envoyé</span>
            @else
                <a href="{{ route('upcycling.projets.show', $projet) }}#devis" class="px-2 py-0.5 rounded-md bg-primary text-white hover:bg-primary/90">
                    <i class="fas fa-file-invoice"></i> Envoyer un devis
                </a>
            @endif
        @endif
    </div>

    @if($deplacable)
        <button type="button" data-avancer
                class="mt-3 w-full text-[11px] font-semibold py-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-primary hover:text-white transition-colors">
            Passer à « {{ \App\Models\ProjetUpcycling::ETAPES[$suivante]['label'] }} » <i class="fas fa-arrow-right ml-1"></i>
        </button>
    @endif
</article>
