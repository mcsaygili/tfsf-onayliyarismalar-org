<x-uye.app-layout :title="__('result_archive.my_results')">
    <a class="mp-back" href="{{ route('competitions.show', $archive['competition']) }}">← {{ __('uye.competitions.back') }}</a>
    @include('uye.competitions.partials.published-scorecards')
</x-uye.app-layout>
