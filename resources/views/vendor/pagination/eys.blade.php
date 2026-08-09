@if ($paginator->hasPages())
    <nav class="ip-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="ip-pagination-info">
            {{ __('eys.users.pagination_info', ['first' => $paginator->firstItem(), 'last' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </div>

        <div class="ip-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-disabled="true">&laquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="is-disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="is-current">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
            @else
                <span class="is-disabled" aria-disabled="true">&raquo;</span>
            @endif
        </div>
    </nav>
@endif
