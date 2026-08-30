@props(['competitions', 'compact' => false])

<div class="jp-task-list {{ $compact ? 'is-compact' : '' }}">
    @forelse ($competitions as $competition)
        <article class="jp-task-item">
            <div class="jp-task-main">
                <div class="jp-task-heading">
                    <div>
                        <h3>{{ $competition->name ?: __('juri.invitation.unnamed_competition') }}</h3>
                        <p>{{ $competition->institution->name }}</p>
                    </div>
                    <span class="ip-badge {{ $competition->status->badgeClass() }}">
                        {{ __('juri.assignments.status.'.$competition->status->value) }}
                    </span>
                </div>

                <div class="jp-category-group" aria-label="{{ __('juri.assignments.categories') }}">
                    @foreach ($competition->categories as $category)
                        <span class="jp-category-tag">{{ $category->name ?: __('juri.assignments.unnamed_category') }}</span>
                    @endforeach
                </div>

                @unless ($compact)
                    <p class="jp-task-note">{{ __('juri.assignments.scope_note') }}</p>
                @endunless

                <a class="jp-task-link" href="{{ route('juri.assignments.show', $competition) }}">
                    {{ __('juri.assignments.view_detail') }} <span aria-hidden="true">→</span>
                </a>
            </div>

            <dl class="jp-task-dates">
                @foreach (['application_starts_at' => 'application_starts', 'application_ends_at' => 'application_ends', 'competition_ends_at' => 'competition_ends'] as $field => $label)
                    <div>
                        <dt>{{ __('juri.assignments.dates.'.$label) }}</dt>
                        <dd>
                            @if ($competition->{$field})
                                <time datetime="{{ $competition->{$field}->toIso8601String() }}">{{ $competition->{$field}->format('d.m.Y H:i') }}</time>
                            @else
                                {{ __('juri.assignments.date_not_set') }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </article>
    @empty
        <div class="jp-task-empty">
            <span class="jp-task-empty-icon"><x-juri.icon name="assignments" /></span>
            <div>
                <h3>{{ __('juri.assignments.empty_title') }}</h3>
                <p>{{ __('juri.assignments.empty_text') }}</p>
            </div>
        </div>
    @endforelse
</div>
