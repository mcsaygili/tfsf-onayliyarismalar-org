<?php

namespace App\Services;

use App\Enums\CompetitionAudience;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\MemberGroup;
use App\Models\ParticipantGender;
use App\Models\User;
use App\Support\CompetitionRules\CompetitionEligibilityEvaluator;
use App\Support\Identity\TurkishIdentityNumber;

class MemberEligibilityService
{
    public function __construct(
        private readonly CompetitionPhaseService $phases,
        private readonly CompetitionEligibilityEvaluator $categories,
    ) {}

    /** @return array{eligible: bool, state: string, violations: array<int, string>} */
    public function forCompetition(Competition $competition, User $user): array
    {
        $violations = [];
        if (! $this->phases->acceptsApplications($competition)) {
            $violations[] = 'applications_not_open';
        }
        if ((int) $user->status !== 1) {
            $violations[] = 'account_inactive';
        }
        if ($user->activeRestrictions()->exists()) {
            $violations[] = 'member_restricted';
        }
        if (! $user->hasVerifiedEmail()) {
            $violations[] = 'email_not_verified';
        }
        if (! $user->date_of_birth) {
            $violations[] = 'profile_birth_date_missing';
        }
        if (! $user->gender) {
            $violations[] = 'profile_gender_missing';
        }
        if ($competition->audience === CompetitionAudience::National && ! TurkishIdentityNumber::isValid($user->tckimlikno)) {
            $violations[] = 'national_identity_required';
        }

        return $this->result($violations);
    }

    /** @return array{eligible: bool, state: string, violations: array<int, string>} */
    public function forCategory(CompetitionCategory $category, User $user): array
    {
        $competition = $category->competition;
        $base = $this->forCompetition($competition, $user);
        $genderId = ParticipantGender::query()->where('code', $user->gender)->value('id');
        $memberGroupId = MemberGroup::query()->where('code', (string) $user->uye_turu)->value('id');
        $categoryResult = $this->categories->evaluateParticipant($category, [
            'birth_date' => $user->date_of_birth?->format('Y-m-d'),
            'gender_id' => $genderId,
            'member_group_ids' => array_values(array_filter([$memberGroupId])),
        ]);

        return $this->result(array_values(array_unique([...$base['violations'], ...$categoryResult['violations']])));
    }

    /** @return array{eligible: bool, state: string, violations: array<int, string>} */
    private function result(array $violations): array
    {
        $actionable = ['email_not_verified', 'profile_birth_date_missing', 'profile_gender_missing', 'national_identity_required'];
        $state = $violations === [] ? 'eligible' : (collect($violations)->every(fn ($item) => in_array($item, $actionable, true)) ? 'action_required' : 'ineligible');

        return ['eligible' => $violations === [], 'state' => $state, 'violations' => $violations];
    }
}
