<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompetitionReportController extends Controller
{
    public function entries(Competition $competition): StreamedResponse
    {
        $competition->load(['entries.user', 'entries.submissions.category.translations', 'entries.submissions.photos']);

        return $this->csv('katilimlar-'.$competition->id.'.csv', ['Üye', 'E-posta', 'Kategori', 'Durum', 'Aktif Fotoğraf', 'Gönderim'], function ($handle) use ($competition) {
            foreach ($competition->entries as $entry) {
                foreach ($entry->submissions as $submission) {
                    fputcsv($handle, [
                        trim($entry->user->first_name.' '.$entry->user->last_name), $entry->user->email,
                        $submission->category->name, $submission->status->value,
                        $submission->photos->whereNull('withdrawn_at')->count(), $entry->submitted_at?->format('Y-m-d H:i'),
                    ], ';', '"', '');
                }
            }
        });
    }

    public function results(Competition $competition): StreamedResponse
    {
        $round = $competition->evaluationRounds()->where('is_final', true)->first() ?? $competition->evaluationRounds()->firstOrFail();
        $round->load(['results.photo.submission.category.translations', 'results.photo.submission.entry.user', 'results.awards.categoryAward.awardReference.translations']);

        return $this->csv('sonuclar-'.$competition->id.'.csv', ['Kategori', 'Sıra', 'Katılımcı', 'Puan', 'Ödül'], function ($handle) use ($round) {
            foreach ($round->results->sortBy('rank') as $result) {
                fputcsv($handle, [
                    $result->photo->submission->category->name, $result->rank,
                    trim($result->photo->submission->entry->user->first_name.' '.$result->photo->submission->entry->user->last_name),
                    $result->average_score,
                    $result->awards->map(fn ($award) => $award->categoryAward->awardReference?->name)->filter()->join(', '),
                ], ';', '"', '');
            }
        });
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
