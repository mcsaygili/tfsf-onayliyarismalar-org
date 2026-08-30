<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    @php
        $copy = __('institution.competitions.regulation');
        $locales = array_keys($regulationPreview['content']);
        $sectionCount = collect($regulationPreview['content'])->max(fn (array $sections) => count($sections)) ?? 0;
        $clauseCount = collect($regulationPreview['content'])->max(fn (array $sections) => collect($sections)->sum(fn (array $section) => count($section['items']))) ?? 0;
    @endphp

    <main class="ip-regulation-stage" x-data="{ locale: @js($locales[0] ?? 'tr') }">
        <section class="ip-regulation-hero" aria-labelledby="regulation-title">
            <div>
                <span class="ip-regulation-eyebrow">{{ $copy['eyebrow'] }}</span>
                <h1 id="regulation-title">{{ $copy['title'] }}</h1>
                <p>{{ $copy['hint'] }}</p>
            </div>
            <div class="ip-regulation-status {{ $regulationReady ? 'is-ready' : 'is-blocked' }}">
                <span class="ip-regulation-status-mark" aria-hidden="true">{{ $regulationReady ? '✓' : '!' }}</span>
                <span>
                    <strong>{{ $regulationReady ? $copy['ready'] : $copy['not_ready'] }}</strong>
                    <small>{{ $regulationReady ? $copy['ready_hint'] : $copy['not_ready_hint'] }}</small>
                </span>
            </div>
        </section>

        @if ($regulationPreview['errors'] !== [])
            <div class="ip-alert ip-alert-warning ip-regulation-alert" role="alert">
                <x-institution.icon name="warning" />
                <div>
                    <div class="ip-alert-title">{{ $copy['errors_title'] }}</div>
                    <ul class="ip-regulation-error-list">
                        @foreach ($regulationPreview['errors'] as $error)
                            <li><strong>{{ strtoupper($error['locale']) }} · {{ $error['item_code'] }}</strong> — {{ $error['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="ip-regulation-workspace">
            <header class="ip-regulation-toolbar">
                <div class="ip-language-tabs" role="tablist" aria-label="{{ __('institution.competitions.language_tabs_label') }}">
                    @foreach ($locales as $locale)
                        <button
                            type="button"
                            class="ip-language-tab"
                            :class="{ 'is-active': locale === @js($locale) }"
                            @click="locale = @js($locale)"
                            role="tab"
                            :aria-selected="(locale === @js($locale)).toString()"
                        >{{ config('locales.supported.'.$locale, strtoupper($locale)) }}</button>
                    @endforeach
                </div>
                <div class="ip-regulation-meta">
                    <span>{{ $copy['generated'] }}</span>
                    <span>{{ trans_choice('institution.competitions.regulation.section_count', $sectionCount, ['count' => $sectionCount]) }}</span>
                    <span>{{ trans_choice('institution.competitions.regulation.clause_count', $clauseCount, ['count' => $clauseCount]) }}</span>
                </div>
            </header>

            @foreach ($regulationPreview['content'] as $locale => $sections)
                <article class="ip-regulation-document" x-show="locale === @js($locale)" @if (! $loop->first) x-cloak @endif role="tabpanel" lang="{{ $locale }}">
                    @forelse ($sections as $sectionIndex => $section)
                        <section class="ip-regulation-section">
                            <h2><span>{{ str_pad((string) ($sectionIndex + 1), 2, '0', STR_PAD_LEFT) }}</span>{{ $section['title'] }}</h2>
                            <ol>
                                @foreach ($section['items'] as $itemIndex => $item)
                                    <li>
                                        <span class="ip-regulation-clause-number">{{ $sectionIndex + 1 }}.{{ $itemIndex + 1 }}</span>
                                        <p>{{ $item['content'] }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @empty
                        <p class="ip-regulation-empty">{{ $copy['empty'] }}</p>
                    @endforelse
                </article>
            @endforeach
        </section>

        <aside class="ip-regulation-note">
            <x-institution.icon name="info" />
            <div><strong>{{ $copy['legal_note_title'] }}</strong><p>{{ $copy['legal_note'] }}</p></div>
        </aside>

        <form method="POST" action="{{ route('institution.competitions.step.update', [$competition, 9]) }}" class="ip-form-actions ip-regulation-actions">
            @csrf @method('PUT')
            <input type="hidden" name="regulation_ready" value="{{ $regulationReady ? '1' : '' }}">
            <a href="{{ route('institution.competitions.step.show', [$competition, 8]) }}" class="ia-btn ia-btn-secondary">← {{ $copy['back'] }}</a>
            <button type="submit" name="action" value="next" class="ia-btn" @disabled(! $regulationReady)>{{ $copy['continue'] }} →</button>
        </form>
    </main>
</x-institution.app-layout>
