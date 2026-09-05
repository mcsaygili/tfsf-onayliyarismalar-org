<?php

namespace Tests\Concerns;

use App\Models\EvaluationCriterion;
use App\Models\Juri;
use App\Services\JuryEvaluationService;

trait CreatesEvaluationRevision
{
    use CreatesSecuritySubmission;

    private function evaluationFixture(): array
    {
        $submission = $this->securitySubmission();
        $competition = $submission->entry->competition;
        $competition->update(['application_ends_at' => now()->subDay(), 'evaluation_starts_at' => now()->subHour(), 'evaluation_ends_at' => now()->addDay(), 'competition_ends_at' => now()->addDay()]);
        $submission->update(['status' => 'approved']);
        $photo = $submission->photos()->create(['disk_path' => 'revision-test.jpg', 'original_filename' => 'private-test.jpg', 'mime_type' => 'image/jpeg', 'file_size_bytes' => 10, 'sha256' => hash('sha256', 'revision-test'), 'sort_order' => 10,
            'declaration' => ['title' => 'Eser', 'location' => 'İzmir', 'taken_on' => '2024-01-01', 'story' => null]]);
        $juror = Juri::factory()->create();
        $assignment = $submission->category->jurorAssignments()->create(['juror_id' => $juror->id]);
        $criterion = EvaluationCriterion::create(['code' => 'revision-criterion', 'status' => true]);
        $criterion = $submission->category->evaluationCriteria()->create(['evaluation_criterion_id' => $criterion->id, 'min_score' => 3, 'max_score' => 9, 'weight' => 1]);
        $round = app(JuryEvaluationService::class)->roundFor($competition);
        $data = app(JuryEvaluationService::class)->evaluationData($assignment, $round);
        $this->assertCount(1, $data['photos']);

        return compact('submission', 'competition', 'photo', 'juror', 'assignment', 'criterion', 'round', 'data');
    }
}
