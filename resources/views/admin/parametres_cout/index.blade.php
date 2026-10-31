@extends('layouts.app')

@section('title', 'Gestion des Barèmes de Coût & Rentabilité — Admin RETISS')
@section('body-class', 'no-hero')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 py-10">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ── HEADER ────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('dashboard.admin') }}" class="text-xs font-semibold text-slate-600 hover:underline">
                    ← Retour au tableau de bord Admin
                </a>
            </div>
            <h1 class="font-display text-3xl font-bold text-gray-900 flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-xl">
                    <i class="fas fa-sliders"></i>
                </span>
                Barèmes de Coûts & Seuils de Rentabilité
            </h1>
            <p class="text-gray-500 mt-1 text-sm">
                Ajustez les formules de valorisation textile (recyclage au kilo, tri, transport, tarif horaire atelier et seuils d'alertes) sans toucher au code.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
            <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ── TABLEAU DES BARÈMES PAR MATIÈRE ─────────────────────── --}}
    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 bg-slate-50 border-b border-gray-200 flex items-center justify-between">
            <h2 class="font-bold text-gray-900 text-sm uppercase tracking-wider flex items-center gap-2">
                <i class="fas fa-table-list text-slate-600"></i> Barèmes configurés par matière textile
            </h2>
            <span class="text-xs text-gray-400">{{ $parametres->count() }} matières enregistrées</span>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($parametres as $param)
            <div class="p-6 hover:bg-slate-50/50 transition-colors">
                <form method="POST" action="{{ route('admin.parametres-cout.update', $param) }}">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 font-bold text-xs flex items-center justify-center">
                                {{ strtoupper(substr($param->matiere, 0, 2)) }}
                            </span>
                            <div>
                                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                                    {{ ucfirst($param->matiere) }}
                                    @if($param->matiere === 'defaut')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">Barème général par défaut</span>
                                    @endif
                                </h3>
                                @if($param->notes)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $param->notes }}</p>
                                @endif
                            </div>
                        </div>

                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-sm transition-all self-start lg:self-auto cursor-pointer">
                            <i class="fas fa-floppy-disk text-xs"></i>
                            <span>Enregistrer les barèmes</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-xs">
                        {{-- Recyclage au kg --}}
                        <div>
                            <label class="block text-[11px] text-gray-500 font-medium mb-1">Recyclage / kg (DT)</label>
                            <input type="number" step="0.05" min="0" name="cout_recyclage_par_kg"
                                   value="{{ old('cout_recyclage_par_kg', $param->cout_recyclage_par_kg) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                        </div>

                        {{-- Tri unitaire --}}
                        <div>
                            <label class="block text-[11px] text-gray-500 font-medium mb-1">Tri fixe (DT)</label>
                            <input type="number" step="0.05" min="0" name="cout_tri_fixe"
                                   value="{{ old('cout_tri_fixe', $param->cout_tri_fixe) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                        </div>

                        {{-- Transport au kg --}}
                        <div>
                            <label class="block text-[11px] text-gray-500 font-medium mb-1">Transport / kg (DT)</label>
                            <input type="number" step="0.05" min="0" name="cout_transport_par_kg"
                                   value="{{ old('cout_transport_par_kg', $param->cout_transport_par_kg) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                        </div>

                        {{-- Majoration complexe % --}}
                        <div>
                            <label class="block text-[11px] text-gray-500 font-medium mb-1">Majoration mixte (%)</label>
                            <input type="number" step="1" min="0" max="100" name="majoration_mixte_pourcent"
                                   value="{{ old('majoration_mixte_pourcent', $param->majoration_mixte_pourcent) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                        </div>

                        {{-- Tarif horaire base --}}
                        <div>
                            <label class="block text-[11px] text-gray-500 font-medium mb-1">Tarif horaire (DT/h)</label>
                            <input type="number" step="0.5" min="0" name="tarif_horaire_defaut"
                                   value="{{ old('tarif_horaire_defaut', $param->tarif_horaire_defaut) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                        </div>

                        {{-- Marge cible verte % --}}
                        <div>
                            <label class="block text-[11px] text-emerald-700 font-bold mb-1">Marge cible (%)</label>
                            <input type="number" step="1" min="0" max="100" name="marge_cible_pourcent"
                                   value="{{ old('marge_cible_pourcent', $param->marge_cible_pourcent) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-emerald-300 text-xs font-bold text-emerald-800 bg-emerald-50/40 focus:border-emerald-500 outline-none">
                        </div>

                        {{-- Marge mini orange % --}}
                        <div>
                            <label class="block text-[11px] text-amber-700 font-bold mb-1">Marge min (%)</label>
                            <input type="number" step="1" min="0" max="100" name="marge_minimale_pourcent"
                                   value="{{ old('marge_minimale_pourcent', $param->marge_minimale_pourcent) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-amber-300 text-xs font-bold text-amber-800 bg-amber-50/40 focus:border-amber-500 outline-none">
                        </div>

                        {{-- Frais plateforme % --}}
                        <div>
                            <label class="block text-[11px] text-gray-500 font-medium mb-1">Commission RETISS (%)</label>
                            <input type="number" step="0.5" min="0" max="100" name="frais_plateforme_pourcent"
                                   value="{{ old('frais_plateforme_pourcent', $param->frais_plateforme_pourcent) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-900 bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                        </div>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
    </div>

</div>
</div>
@endsection

