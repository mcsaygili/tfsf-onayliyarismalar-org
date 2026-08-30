<?php

namespace App\Support\CompetitionRegulations;

use InvalidArgumentException;

class RegulationTemplateRenderer
{
    public function __construct(private readonly CompetitionRegulationDefinitionRegistry $definitions) {}

    /** @return array<int, string> */
    public function validate(string $template, string $scope = 'once'): array
    {
        $allowed = array_keys($this->definitions->tokensForScope($scope));

        return collect($this->tokens($template))
            ->reject(fn (string $token) => in_array($token, $allowed, true))
            ->map(fn (string $token) => 'Bilinmeyen şablon alanı: {{ '.$token.' }}')
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $context */
    public function render(string $template, array $context, string $scope = 'once'): string
    {
        $errors = $this->validate($template, $scope);
        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        return trim((string) preg_replace_callback(
            '/\{\{\s*([a-z_][a-z0-9_.]*)\s*\}\}/i',
            function (array $match) use ($context): string {
                $value = data_get($context, $match[1]);
                if ($value === null || $value === '') {
                    throw new InvalidArgumentException('Şablon alanı için değer bulunamadı: {{ '.$match[1].' }}');
                }

                return is_array($value) ? implode(', ', array_filter($value, 'filled')) : (string) $value;
            },
            $template,
        ));
    }

    /** @return array<int, string> */
    private function tokens(string $template): array
    {
        preg_match_all('/\{\{\s*([a-z_][a-z0-9_.]*)\s*\}\}/i', $template, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
