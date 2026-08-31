<?php

use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\CompetitionEvaluationRound;
use App\Models\CompetitionResultPublication;
use App\Notifications\Juri\EvaluationDeadlineReminderNotification;
use App\Services\NotificationDispatchService;
use App\Services\ResultPublicationService;
use App\Services\SystemHealthService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => SystemHealthService::beatScheduler())
    ->name('system-health-scheduler-heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(function (): void {
    CompetitionEvaluationRound::query()
        ->where('round_number', 1)->where('status', 'open')
        ->whereBetween('closes_at', [now(), now()->addDays(2)])
        ->with('competition.translations')->get()
        ->each(function ($round): void {
            CompetitionCategoryJurorAssignment::query()
                ->whereNotNull('juror_id')
                ->whereHas('category', fn ($query) => $query->where('competition_id', $round->competition_id))
                ->whereDoesntHave('evaluationSubmissions', fn ($query) => $query->where('competition_evaluation_round_id', $round->id))
                ->with('juror')->get()->each(function ($assignment) use ($round): void {
                    if (! $assignment->juror) {
                        return;
                    }

                    $deadline = $round->closes_at?->format('d.m.Y H:i') ?? '—';
                    $assignment->juror->notifyNow(
                        new EvaluationDeadlineReminderNotification($round->competition, $assignment->competition_category_id, $deadline, ['database'])
                    );
                    app(NotificationDispatchService::class)->queueEvaluationDeadline(
                        $assignment->juror,
                        $round->competition,
                        $assignment->competition_category_id,
                        $deadline,
                    );
                });
        });
})->name('jury-evaluation-deadline-reminders')->dailyAt('09:00')->withoutOverlapping();

Schedule::call(function (): void {
    CompetitionResultPublication::query()
        ->whereNull('notified_at')->whereNull('withdrawn_at')->where('published_at', '<=', now())
        ->with('competition')->get()->each(fn ($publication) => app(ResultPublicationService::class)->notify($publication));
})->name('scheduled-result-publications')->everyTenMinutes()->withoutOverlapping();
