<?php

namespace App\Services;

use App\Models\SiteAnalysis;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Throwable;

use function Laravel\Ai\agent;

class AnalysisSummarizer
{
    /**
     * Produce a short, actionable summary of an analysis payload.
     *
     * @return array{headline: string, points: list<string>}|null
     */
    public function summarize(SiteAnalysis $analysis): ?array
    {
        $prompt = $this->buildPrompt($analysis);

        if ($prompt === null) {
            return null;
        }

        try {
            $response = agent(
                instructions: <<<'TEXT'
                    You are a web performance consultant reviewing a Lighthouse
                    report for a WordPress site. Write a short, plain-language
                    summary for a site owner: one headline sentence and 2-4
                    concrete, prioritized recommendations. Reference the actual
                    numbers from the report. Be direct, no filler, no markdown.
                    TEXT,
                schema: fn (JsonSchema $schema): array => [
                    'headline' => $schema->string()->required(),
                    'points' => $schema->array()->items($schema->string())->required(),
                ],
            )->prompt($prompt, provider: 'berget', timeout: 120);

            $structured = $response->structured;

            if (! isset($structured['headline'], $structured['points']) || ! is_array($structured['points'])) {
                return null;
            }

            return [
                'headline' => (string) $structured['headline'],
                'points' => array_values(array_filter($structured['points'], 'is_string')),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Compact, token-efficient description of the analysis payload.
     */
    private function buildPrompt(SiteAnalysis $analysis): ?string
    {
        $data = $analysis->data;

        if (! is_array($data)) {
            return null;
        }

        $lines = [
            "Site: {$analysis->site->url}",
            'Strategy: '.($data['strategy'] ?? 'unknown'),
            '',
            'Category scores (0-100):',
        ];

        foreach ($data['scores'] ?? [] as $category => $score) {
            $lines[] = '- '.$category.': '.($score === null ? 'n/a' : $score);
        }

        $lines[] = '';
        $lines[] = 'Core metrics:';

        foreach ($data['metrics'] ?? [] as $metric) {
            $lines[] = '- '.$metric['label'].': '.$metric['value'];
        }

        if (! empty($data['opportunities'])) {
            $lines[] = '';
            $lines[] = 'Top opportunities:';

            foreach ($data['opportunities'] as $opportunity) {
                $lines[] = '- '.$opportunity['title'].' (saves '.$opportunity['savings'].')';
            }
        }

        return implode("\n", $lines);
    }
}
