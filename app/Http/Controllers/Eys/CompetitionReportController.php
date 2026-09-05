<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\CompetitionResultPresentationService;
use App\Services\ResultSnapshotBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompetitionReportController extends Controller
{
    public function entries(Competition $competition): StreamedResponse
    {
        $competition->load(['entries.user', 'entries.submissions.category.translations', 'entries.submissions.photos']);

        return $this->csv('katilimlar-'.$competition->id.'.csv', ['Üye', 'E-posta', 'Kategori', 'Durum', 'Aktif Fotoğraf', 'Gönderim'], function ($handle) use ($competition) {
            foreach ($competition->entries as $entry) {
                foreach ($entry->submissions as $submission) {
                    fputcsv($handle, array_map($this->safeCell(...), [
                        trim($entry->user->first_name.' '.$entry->user->last_name), $entry->user->email,
                        $submission->category->name, $submission->status->value,
                        $submission->photos->whereNull('withdrawn_at')->count(), $entry->submitted_at?->format('Y-m-d H:i'),
                    ]), ';', '"', '');
                }
            }
        });
    }

    public function results(Request $request, Competition $competition): StreamedResponse
    {
        $input = $request->validate(['version' => ['nullable', 'integer', 'min:1']]);
        $version = $input['version'] ?? ($competition->results_published_at ? $competition->results_publication_version : null);
        if ($version) {
            $snapshot = $competition->resultPublications()->where('version', $version)->firstOrFail()->snapshot;
        } else {
            $round = $competition->evaluationRounds()->where('is_final', true)->first() ?? $competition->evaluationRounds()->firstOrFail();
            $snapshot = app(ResultSnapshotBuilder::class)->build($competition, $round);
        }
        $presentation = app(CompetitionResultPresentationService::class);

        return $this->csv('sonuclar-'.$competition->id.($version ? '-v'.$version : '').'.csv', ['Kategori', 'Sıra', 'Katılımcı', 'Puan', 'Ödül', 'Yayın Sürümü'], function ($handle) use ($snapshot, $version, $presentation) {
            foreach (collect($snapshot['results'])->sortBy('rank') as $result) {
                fputcsv($handle, array_map($this->safeCell(...), [
                    $presentation->translated($result['category']), $result['rank'], $result['participant'], $result['average_score'],
                    collect($result['awards'])->map(fn ($award) => $presentation->translated($award['name']))->filter()->join(', '), $version ?? '',
                ]), ';', '"', '');
            }
        });
    }

    private function safeCell(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^\s*[=+@-]|^[\t\r\n]/u', $value) ? "'".$value : $value;
    }

    private function csv(string $filename, array $headers, \Closure $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $writer): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';', '"', '');
            $writer($handle);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
