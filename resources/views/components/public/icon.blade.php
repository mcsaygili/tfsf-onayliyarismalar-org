@props(['name', 'class' => null])

@php
    $resolved = str_starts_with($name, 'competition-')
        ? App\Support\CompetitionIcons\CompetitionIconRegistry::componentName($name)
        : match ($name) {
            'audience-national' => 'city',
            'audience-international' => 'country',
            'infrastructure-tfsf' => 'settings',
            'infrastructure-external' => 'arrow-up',
            'location' => 'city',
            'approval' => 'list-check',
            'categories' => 'layers',
            'calendar' => 'calendar',
            'search' => 'search',
            'filter' => 'filter',
            'document' => 'document',
            'results' => 'award',
            'arrow-right' => 'chevron-right',
            'back' => 'back',
            'menu' => 'layers',
            default => 'competitions',
        };
@endphp

<x-eys.icon :name="$resolved" :class="$class" aria-hidden="true" />
