<?php

namespace Tests\Feature;

use App\Models\EvaluationCriterion;
use App\Models\Juri;
use App\Models\JuryScore;
use App\Services\CompetitionResultService;
use App\Services\CompetitionSubmissionPhotoService;
use App\Services\MemberScorecardService;
use App\Services\ResultSnapshotBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesSecuritySubmission;
use Tests\TestCase;

class WeightedScoreConsistencyTest extends TestCase
{
    use CreatesSecuritySubmission, RefreshDatabase;

    public static function scores(): array
    {
        return [
            'unequal weights' => [[1, 3], [[3, 7]], 6.0],
            'fractional average' => [[1, 1], [[7, 8]], 7.5],
            'fractional weights' => [[0.25, 0.75], [[3, 7]], 6.0],
            'unrounded round average' => [[1, 2], [[3, 4], [4, 4]], 3.83],
            'partial criteria have the same denominator' => [[1, 3], [[3, 7], [9]], 6.6],
        ];
    }

    #[DataProvider('scores')]
    public function test_scorecard_matches_official_weighted_result(array $weights, array $votes, float $expected): void
    {
        Storage::fake('local');
        $submission = $this->securitySubmission();
        $photo = app(CompetitionSubmissionPhotoService::class)->fromUpload($submission,
            new UploadedFile(base_path('tests/Fixtures/identity-metadata.jpg'), 'photo.jpg', 'image/jpeg', null, true), null, []);
        $round = $submission->entry->competition->evaluationRounds()->create(['name' => 'Round', 'round_number' => 1]);
        $criteria = [];
        foreach ($weights as $i => $weight) {
            $criterion = EvaluationCriterion::create(['code' => 'criterion-'.$i, 'status' => true]);
            $criteria[] = $submission->category->evaluationCriteria()->create([
                'evaluation_criterion_id' => $criterion->id, 'min_score' => 3, 'max_score' => 9, 'weight' => $weight, 'sort_order' => $i,
            ]);
        }
        foreach ($votes as $index => $scores) {
            $assignment = $submission->category->jurorAssignments()->create(['juror_id' => Juri::factory()->create()->id, 'sort_order' => $index]);
            foreach ($scores as $i => $score) {
                JuryScore::create(['competition_evaluation_round_id' => $round->id, 'juror_assignment_id' => $assignment->id,
                    'submission_photo_id' => $photo->id, 'criterion_assignment_id' => $criteria[$i]->id, 'score' => $score, 'submitted_at' => now()]);
            }
        }
        $submission->update(['status' => 'approved']);
        app(CompetitionResultService::class)->aggregate($round);
        $card = app(MemberScorecardService::class)->forEntry($submission->entry->fresh('submissions.photos'))[$photo->id][0];
        $this->assertEquals($expected, $round->results()->firstOrFail()->average_score);
        $this->assertEquals($expected, $card['average']);
        $this->assertCount(count($votes), $card['scores']);
        $snapshot = app(ResultSnapshotBuilder::class)->build($submission->entry->competition, $round, true);
        $archived = $snapshot['member_entries'][0]['photos'][0]['scorecards'][0];
        $this->assertEquals($expected, $archived['average']);
        $this->assertEquals(array_column($card['scores'], 'score'), array_column($archived['scores'], 'score'));
    }
}
