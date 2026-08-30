<?php

namespace App\Support\CompetitionRegulations;

class RegulationConditionMatcher
{
    /** @param array<string, mixed>|null $conditions @param array<string, mixed> $context */
    public function matches(?array $conditions, array $context): bool
    {
        if ($conditions === null || $conditions === []) {
            return true;
        }

        $rules = isset($conditions['all']) ? $conditions['all'] : $this->legacyRules($conditions);

        return collect($rules)->every(fn (array $rule) => $this->matchesRule($rule, $context));
    }

    /** @param array<string, mixed> $rule @param array<string, mixed> $context */
    private function matchesRule(array $rule, array $context): bool
    {
        $actual = data_get($context, (string) ($rule['field'] ?? ''));
        $expected = $rule['value'] ?? null;

        return match ($rule['operator'] ?? 'equals') {
            'not_equals' => $actual != $expected,
            'in' => in_array($actual, is_array($expected) ? $expected : array_map('trim', explode(',', (string) $expected)), true),
            'exists' => $actual !== null,
            'not_empty' => filled($actual),
            'contains' => is_array($actual)
                ? in_array($expected, $actual, true)
                : str_contains((string) $actual, (string) $expected),
            default => $actual == $expected,
        };
    }

    /** @param array<string, mixed> $conditions @return array<int, array<string, mixed>> */
    private function legacyRules(array $conditions): array
    {
        $fieldMap = [
            'audience' => 'competition.audience',
            'infrastructure_provider' => 'competition.infrastructure_provider',
            'competition_type' => 'competition.type_code',
        ];

        return collect($conditions)->map(function ($value, string $field) use ($fieldMap): array {
            $values = (array) $value;

            return [
                'field' => $fieldMap[$field] ?? $field,
                'operator' => count($values) > 1 ? 'in' : 'equals',
                'value' => count($values) > 1 ? $values : ($values[0] ?? null),
            ];
        })->values()->all();
    }
}
