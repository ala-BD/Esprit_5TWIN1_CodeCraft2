@extends('upcycling.layouts.upcycling')

@section('title', $atelier->nom)

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.ateliers.index') }}" class="hover:text-primary transition-colors">Ateliers</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">{{ $atelier->nom }}</span>
@endsection

@php $estMonAtelier = $atelier->user_id === Auth::id(); @endphp

@section('upcycling-content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Profil --}}
    <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start justify-between mb-5">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center">
                    <i class="fas {{ $atelier->icone }} text-primary text-2xl"></i>
                </div>
                @unless($atelier->actif)
                    <span class="px-3 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-500">Indisponible</span>
                @endunless
            </div>

            <h1 class="font-display text-xl font-bold text-gray-900">{{ $atelier->nom }}</h1>
            <p class="text-primary text-sm font-medium mb-4">{{ $atelier->specialite_label }}</p>

            <div class="flex items-center gap-1 mb-5">
                @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star {{ $i <= round($atelier->note_moyenne) ? 'text-amber-400' : 'text-gray-200' }}"></i>
                @endfor
                <span class="ml-2 text-sm text-gray-600">
                    {{ $atelier->note_moyenne > 0 ? number_format($atelier->note_moyenne, 1) . '/5' : 'Pas encore noté' }}
                </span>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><span class="text-gray-500">Localisation</span><span class="font-medium text-gray-800 text-right">{{ $atelier->localisation }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Tarif horaire</span><span class="font-medium text-gray-800">{{ number_format($atelier->tarif_horaire, 0) }} DT</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Projets en cours</span><span class="font-medium text-gray-800">{{ $atelier->charge }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Contact</span><span class="font-medium text-gray-800">{{ $atelier->user->full_name }}</span></div>
            </div>

            @if($atelier->portfolio_url)
                <a href="{{ $atelier->portfolio_url }}" target="_blank" rel="noopener"
                   class="mt-5 flex items-center justify-center gap-2 w-full border-2 border-gray-200 hover:border-primary hover:text-primary text-gray-700 font-semibold py-2.5 rounded-xl transition-all text-sm">
                    <i class="fas fa-external-link-alt"></i> Voir le portfolio externe
                </a>
            @endif
        </div>

        @if($estMonAtelier)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm">Gérer mon atelier</h3>
            <a href="{{ route('upcycling.ateliers.edit', $atelier) }}"
               class="flex items-center gap-3 w-full bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold px-4 py-3 rounded-xl transition-all text-sm">
                <i class="fas fa-pen"></i> Modifier mon profil
            </a>
            <form method="POST" action="{{ route('upcycling.ateliers.destroy', $atelier) }}"
                  onsubmit="return confirm('Supprimer définitivement votre profil atelier ?')">
                @csrf @method('DELETE')
                <button class="flex items-center gap-3 w-full bg-red-50 hover:bg-red-100 text-red-600 font-semibold px-4 py-3 rounded-xl transition-all text-sm">
                    <i class="fas fa-trash-alt"></i> Supprimer mon profil
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Présentation + réalisations --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2"><i class="fas fa-info-circle text-primary"></i> Présentation</h3>
            <p class="text-sm text-gray-600 leading-relaxed">{{ $atelier->description ?? 'Cet atelier n\'a pas encore rédigé de présentation.' }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2"><i class="fas fa-images text-primary"></i> Réalisations</h3>

            @forelse($realisations as $projet)
                <div class="flex gap-4 py-4 border-b border-gray-50 last:border-0">
                    <div class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ \App\Models\Atelier::ICONS[$projet->categorie_produit] ?? 'fa-cut' }} text-green-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold text-gray-900 text-sm">{{ $projet->produit_final }}</p>
                            @if($projet->note_client)
                                <span class="text-xs text-gray-500 flex-shrink-0"><i class="fas fa-star text-amber-400"></i> {{ $projet->note_client }}/5</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400">
                            À partir d'un(e) {{ $projet->type_vetement }} en {{ $projet->matiere }} · livré le {{ $projet->date_fin?->format('d/m/Y') }}
                        </p>
                        @if($projet->commentaire_client)
                            <p class="text-sm text-gray-600 italic mt-1">« {{ $projet->commentaire_client }} » — {{ $projet->client->prenom ?? $projet->client->name }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-10">
                    <i class="fas fa-cut text-gray-300 text-3xl mb-3 block"></i>
                    <p class="text-gray-400 text-sm">Aucune réalisation terminée pour le moment.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
