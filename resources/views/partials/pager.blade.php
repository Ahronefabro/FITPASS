{{-- Numbered pagination for a length-aware paginator, the same look as Staff
     Members: "Showing 11–20 of 57   ‹ 1 2 3 … 6 ›". Shows nothing while
     everything fits on one page. Pass the paginator as $paginator; build it
     with ->withQueryString() so the page links keep the current filter. --}}
@if ($paginator->hasPages())
    @php
        $last = $paginator->lastPage();
        $current = min($paginator->currentPage(), $last);
        // First, last, and the pages either side of the current one.
        $pages = collect([1, $last])
            ->merge(range(max(1, $current - 1), min($last, $current + 1)))
            ->unique()->sort()->values();
        $shown = 0;
    @endphp

    <div class="pager">
        <span class="pager__info">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </span>

        <nav class="pager__nav" aria-label="Pagination">
            @if ($paginator->onFirstPage())
                <span class="pager__btn disabled" aria-disabled="true">‹</span>
            @else
                <a class="pager__btn" href="{{ $paginator->url($current - 1) }}" aria-label="Previous page">‹</a>
            @endif

            @foreach ($pages as $page)
                {{-- A gap of exactly one page shows that page instead of a lone "…" --}}
                @if ($page - $shown === 2)
                    <a class="pager__btn" href="{{ $paginator->url($page - 1) }}">{{ $page - 1 }}</a>
                @elseif ($page - $shown > 2)
                    <span class="pager__gap">…</span>
                @endif

                @if ($page === $current)
                    <span class="pager__btn active" aria-current="page">{{ $page }}</span>
                @else
                    <a class="pager__btn" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                @endif
                @php $shown = $page; @endphp
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pager__btn" href="{{ $paginator->url($current + 1) }}" aria-label="Next page">›</a>
            @else
                <span class="pager__btn disabled" aria-disabled="true">›</span>
            @endif
        </nav>
    </div>
@endif
