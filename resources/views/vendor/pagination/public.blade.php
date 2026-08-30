@if ($paginator->hasPages())
    <nav class="pf-pagination" role="navigation" aria-label="{{ __('public.pagination') }}">
        @if ($paginator->onFirstPage())<span aria-disabled="true">{{ __('pagination.previous') }}</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous') }}</a>@endif
        <div>
            @foreach ($elements as $element)
                @if (is_string($element))<span aria-disabled="true">{{ $element }}</span>@endif
                @if (is_array($element))@foreach ($element as $page => $url)@if ($page == $paginator->currentPage())<span class="is-current" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif @endforeach @endif
            @endforeach
        </div>
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}</a>@else<span aria-disabled="true">{{ __('pagination.next') }}</span>@endif
    </nav>
@endif
