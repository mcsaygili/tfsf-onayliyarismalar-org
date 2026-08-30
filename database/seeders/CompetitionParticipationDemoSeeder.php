<?php

namespace Database\Seeders;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionEntryStatus;
use App\Enums\CompetitionStatus;
use App\Enums\CompetitionSubmissionStatus;
use App\Enums\Module;
use App\Models\AgeEligibilityRule;
use App\Models\AwardReference;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\EvaluationCriterion;
use App\Models\EysUser;
use App\Models\Juri;
use App\Models\MemberGroup;
use App\Models\ParticipantGender;
use App\Models\Permission;
use App\Models\ProcessingMethod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

/**
 * Local UI/demo fixture for the member-to-jury competition lifecycle.
 * It is intentionally not registered in DatabaseSeeder.
 */
class CompetitionParticipationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompetitionCategoryReferenceSeeder::class,
            EvaluationCriterionSeeder::class,
            AwardReferenceSeeder::class,
        ]);

        $eysUser = EysUser::updateOrCreate(['email' => 'qa.eys@example.test'], [
            'first_name' => 'EYS',
            'last_name' => 'Test Kullanıcısı',
            'password' => Hash::make('password'),
            'status' => true,
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        $permission = Permission::firstOrCreate(['name' => 'institution.competitions.manage', 'guard_name' => 'eys']);
        $eysUser->givePermissionTo($permission);

        $member = User::updateOrCreate(['email' => 'qa.member@example.test'], [
            'username' => 'qa-member',
            'first_name' => 'Deniz',
            'last_name' => 'Fotoğrafçı',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'status' => 1,
            'gender' => 'female',
            'date_of_birth' => '1992-05-12',
            'uye_turu' => 3,
        ]);
        $juror = Juri::updateOrCreate(['email' => 'qa.juror@example.test'], [
            'first_name' => 'Selin',
            'last_name' => 'Jüri',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'status' => true,
            'registration_source' => 'eys',
        ]);

        $openCompetition = $this->competition('[QA-DEMO-OPEN]', [
            'application_starts_at' => now()->subDay(),
            'application_ends_at' => now()->addDays(10),
            'competition_ends_at' => now()->addDays(20),
            'evaluation_starts_at' => now()->addDays(21),
            'evaluation_ends_at' => now()->addDays(28),
        ], 'Kent ve İnsan Fotoğraf Yarışması', 'City and People Photography Competition');
        $this->category($openCompetition, 'Sokak Fotoğrafı', 'Street Photography');

        $evaluationCompetition = $this->competition('[QA-DEMO-EVALUATION]', [
            'application_starts_at' => now()->subDays(30),
            'application_ends_at' => now()->subDays(10),
            'competition_ends_at' => now()->subDays(3),
            'evaluation_starts_at' => now()->subDay(),
            'evaluation_ends_at' => now()->addDays(7),
        ], 'Doğanın İzleri Fotoğraf Yarışması', 'Traces of Nature Photography Competition');
        $category = $this->category($evaluationCompetition, 'Doğa', 'Nature');
        $category->awards()->updateOrCreate([
            'award_reference_id' => AwardReference::where('code', 'first-prize')->value('id'),
        ], ['quantity' => 1, 'sort_order' => 10]);
        $category->jurorAssignments()->firstOrCreate(['juror_id' => $juror->id], ['sort_order' => 10]);

        $entry = $evaluationCompetition->entries()->updateOrCreate(['user_id' => $member->id], [
            'status' => CompetitionEntryStatus::Approved,
            'eligibility_snapshot' => ['eligible' => true, 'state' => 'eligible', 'violations' => []],
            'consent_at' => now()->subDays(10),
            'submitted_at' => now()->subDays(10),
            'approved_at' => now()->subDays(9),
        ]);
        $submission = $entry->submissions()->updateOrCreate(['competition_category_id' => $category->id], [
            'status' => CompetitionSubmissionStatus::Approved,
            'eligibility_snapshot' => ['eligible' => true, 'violations' => []],
            'submitted_at' => now()->subDays(10),
            'approved_at' => now()->subDays(9),
        ]);

        $bytes = file_get_contents(base_path('tests/Fixtures/photo-without-exif.jpg'));
        $privatePath = "competition-submissions/{$evaluationCompetition->id}/{$submission->id}/qa-demo.jpg";
        $juryPath = "competition-submissions/{$evaluationCompetition->id}/{$submission->id}/qa-demo-jury.jpg";
        Storage::disk('local')->put($privatePath, $bytes);
        Storage::disk('local')->put($juryPath, $bytes);
        $submission->photos()->updateOrCreate(['sha256' => hash('sha256', $bytes)], [
            'disk_path' => $privatePath,
            'jury_path' => $juryPath,
            'original_filename' => 'qa-demo.jpg',
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => strlen($bytes),
            'width' => 800,
            'height' => 600,
            'metadata_snapshot' => ['exif_missing' => true],
            'processing_method_ids' => [],
            'eligibility_snapshot' => ['eligible' => true, 'violations' => []],
            'sort_order' => 10,
        ]);
    }

    private function competition(string $marker, array $dates, string $trName, string $enName): Competition
    {
        $competition = Competition::where('partners', $marker)->first();
        if (! $competition) {
            $competition = Competition::factory()->create(array_merge([
                'audience' => CompetitionAudience::International,
                'partners' => $marker,
            ], $dates));
        }
        $competition->forceFill(array_merge($dates, [
            'audience' => CompetitionAudience::International,
            'status' => CompetitionStatus::Approved,
            'published_at' => now()->subMonth(),
            'results_published_at' => null,
        ]))->save();
        $competition->upsertTranslations([
            'tr' => ['name' => $trName, 'subject' => 'Fotoğraf yoluyla çevremizi görünür kılmak.', 'purpose' => 'Nitelikli fotoğraf üretimini ve paylaşımını desteklemek.'],
            'en' => ['name' => $enName, 'subject' => 'Making our surroundings visible through photography.', 'purpose' => 'Supporting the production and sharing of quality photography.'],
        ]);

        return $competition;
    }

    private function category(Competition $competition, string $trName, string $enName)
    {
        $category = $competition->categories()->firstOrCreate(['sort_order' => 10], [
            'max_photos_per_participant' => 4,
            'age_eligibility_rule_id' => AgeEligibilityRule::where('code', 'no-age-check')->value('id'),
        ]);
        $category->update([
            'max_photos_per_participant' => 4,
            'age_eligibility_rule_id' => AgeEligibilityRule::where('code', 'no-age-check')->value('id'),
        ]);
        $category->upsertTranslations(['tr' => ['name' => $trName], 'en' => ['name' => $enName]]);
        $category->genders()->sync([ParticipantGender::where('code', 'no-check')->value('id')]);
        $category->memberGroups()->sync([MemberGroup::where('code', 'no-membership-check')->value('id')]);
        $category->captureDevices()->sync([CaptureDevice::where('code', 'no-device-check')->value('id')]);
        $category->processingMethods()->sync([ProcessingMethod::where('code', 'no-processing-check')->value('id')]);
        $criterion = EvaluationCriterion::where('code', 'general-evaluation')->firstOrFail();
        $category->evaluationCriteria()->updateOrCreate(['evaluation_criterion_id' => $criterion->id], [
            'min_score' => 3,
            'max_score' => 9,
            'weight' => 1,
            'sort_order' => 10,
        ]);

        return $category->fresh();
    }
}
