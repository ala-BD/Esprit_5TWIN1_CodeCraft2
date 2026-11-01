{{-- Discussion du projet : client, atelier et styliste IA --}}
@php
    $monRole      = $estProprietaire ? 'CLIENT' : 'ATELIER';
    $autrePartie  = $estProprietaire ? $projet->atelier?->nom : $projet->client->full_name;
    $peutEcrire   = $projet->statut !== 'ANNULE';
    $suggestions  = $projet->produit_final
        ? ['Quelle doublure ou finition choisir ?', 'Comment entretenir le produit fini ?', 'Comment réduire le coût ?']
        : ['Quelle idée convient le mieux à mon usage ?', 'Mon vêtement est-il assez solide ?', 'Quelle idée est la plus écologique ?'];
@endphp

<section id="discussion" class="bg-white rounded-3xl shadow-sm border border-gray-100 mt-6 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h2 class="font-display font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-comments text-primary"></i> Discussion du projet
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $autrePartie ? "Échangez avec {$autrePartie}, ou" : 'En attendant de choisir un atelier,' }}
                posez vos questions au <span class="font-semibold text-violet-600">styliste IA</span>, qui connaît tout votre projet.
            </p>
        </div>
        <span class="text-xs text-gray-400">{{ $projet->messages->count() }} message(s)</span>
    </div>

    {{-- Messages --}}
    <div id="fil-messages" class="px-6 py-5 space-y-4 max-h-[28rem] overflow-y-auto bg-slate-50/60">
        @forelse($projet->messages as $message)
            @php $estMoi = $message->auteur === $monRole; @endphp
            <div class="flex gap-3 {{ $estMoi ? 'flex-row-reverse' : '' }}">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-xs font-bold
                    {{ $message->est_ia ? 'bg-gradient-to-br from-violet-600 to-pink-500 text-white' : ($estMoi ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-600') }}">
                    @if($message->est_ia)
                        <i class="fas fa-wand-magic-sparkles"></i>
                    @else
                        {{ $message->user?->initials ?? '?' }}
                    @endif
                </div>
                <div class="max-w-[75%] {{ $estMoi ? 'text-right' : '' }}">
                    <p class="text-[11px] text-gray-400 mb-1">
                        <span class="font-semibold {{ $message->est_ia ? 'text-violet-600' : 'text-gray-600' }}">{{ $message->nom_auteur }}</span>
                        · {{ $message->created_at->diffForHumans() }}
                    </p>
                    <div class="inline-block text-left text-sm leading-relaxed px-4 py-3 rounded-2xl
                        {{ $message->est_ia ? 'bulle-ia bg-violet-50 border border-violet-100 text-gray-800'
                            : ($estMoi ? 'bulle-moi bg-primary text-white' : 'bulle-ia bg-white border border-gray-100 text-gray-700') }}">
                        {!! nl2br(e($message->contenu)) !!}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-violet-100 to-pink-100 flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-wand-magic-sparkles text-violet-500 text-xl"></i>
                </div>
                <p class="text-sm font-semibold text-gray-700">Aucun message pour l'instant</p>
                <p class="text-xs text-gray-500 mt-1">Une question sur votre projet ? Le styliste IA vous répond tout de suite.</p>
            </div>
        @endforelse

        <div id="ia-redige" class="hidden flex gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-600 to-pink-500 text-white flex items-center justify-center text-xs"><i class="fas fa-wand-magic-sparkles"></i></div>
            <div class="bulle-ia bg-violet-50 border border-violet-100 rounded-2xl px-4 py-3 text-violet-500 points-ia text-lg leading-none">
                <span>●</span><span>●</span><span>●</span>
            </div>
        </div>
    </div>

    {{-- Saisie --}}
    @if($peutEcrire)
    <form method="POST" action="{{ route('upcycling.messages.store', $projet) }}" id="form-message" class="px-6 py-4 border-t border-gray-100" novalidate>
        @csrf
        <div class="flex flex-wrap gap-2 mb-3">
            @foreach($suggestions as $suggestion)
                <button type="button" data-suggestion="{{ $suggestion }}"
                        class="text-[11px] font-semibold px-3 py-1.5 rounded-full bg-violet-50 text-violet-700 hover:bg-violet-100 transition-colors">
                    <i class="fas fa-lightbulb mr-1"></i>{{ $suggestion }}
                </button>
            @endforeach
        </div>

        <textarea name="contenu" id="contenu-message" rows="2" maxlength="1000"
                  placeholder="{{ $autrePartie ? "Votre message à {$autrePartie} ou votre question au styliste IA…" : 'Votre question au styliste IA…' }}"
                  class="w-full px-4 py-3 rounded-2xl border {{ $errors->has('contenu') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} focus:outline-none focus:ring-2 focus:ring-primary text-sm resize-none">{{ old('contenu') }}</textarea>
        @error('contenu')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror

        <div class="flex flex-wrap justify-end gap-2 mt-3">
            <button type="submit" name="ia" value="1"
                    class="inline-flex items-center gap-2 text-sm font-bold text-white px-5 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-pink-500 hover:shadow-lg transition-all">
                <i class="fas fa-wand-magic-sparkles"></i> Demander au styliste IA
            </button>
            @if($autrePartie)
                <button type="submit" name="ia" value="0" class="btn-primary !py-2.5 !px-5 !rounded-xl">
                    <i class="fas fa-paper-plane"></i> Envoyer à {{ \Illuminate\Support\Str::limit($autrePartie, 22) }}
                </button>
            @endif
        </div>
    </form>
    @endif
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fil = document.getElementById('fil-messages');
    if (fil) fil.scrollTop = fil.scrollHeight;

    const form = document.getElementById('form-message');
    if (!form) return;
    const champ = document.getElementById('contenu-message');

    form.querySelectorAll('[data-suggestion]').forEach((bouton) => {
        bouton.addEventListener('click', () => { champ.value = bouton.dataset.suggestion; champ.focus(); });
    });

    form.addEventListener('submit', (e) => {
        if (e.submitter && e.submitter.value === '1' && champ.value.trim().length >= 2) {
            document.getElementById('ia-redige').classList.remove('hidden');
            fil.scrollTop = fil.scrollHeight;
        }
        form.querySelectorAll('button[type=submit]').forEach((b) => b.classList.add('opacity-60', 'pointer-events-none'));
    });
});
</script>
