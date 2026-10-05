{{-- Pagination compacte — attend $paginator (vue de pagination Laravel) --}}
@if ($paginator->hasPages())
<nav class="flex items-center justify-between gap-3" aria-label="Pagination">
    <p class="text-slate-500 num">
        {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}
    </p>

    <div class="flex items-center gap-1">
        @if ($paginator->onFirstPage())
            <span class="a-btn opacity-40 cursor-not-allowed" aria-disabled="true"><i class="fas fa-chevron-left"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="a-btn" rel="prev" aria-label="Page précédente"><i class="fas fa-chevron-left"></i></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1.5 text-slate-400">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="a-btn a-btn-primary num" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="a-btn num">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="a-btn" rel="next" aria-label="Page suivante"><i class="fas fa-chevron-right"></i></a>
        @else
            <span class="a-btn opacity-40 cursor-not-allowed" aria-disabled="true"><i class="fas fa-chevron-right"></i></span>
        @endif
    </div>
</nav>
@endif
