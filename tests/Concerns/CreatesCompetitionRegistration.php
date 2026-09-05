<?php

namespace Tests\Concerns;

use App\Models\InstitutionStaff;
use App\Models\MemberGroup;
use App\Services\CompetitionRegistrationService;
use Illuminate\Http\UploadedFile;

trait CreatesCompetitionRegistration
{
    use CreatesSecuritySubmission;

    private function registrationFixture(int $minimum = 1): array
    {
        $submission = $this->securitySubmission();
        $competition = $submission->entry->competition;
        $competition->update(['registration_required' => true, 'registration_document_min' => $minimum, 'registration_reviewer' => 'institution']);
        $member = $submission->entry->user;
        $member->update(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $submission->category->memberGroups()->sync([MemberGroup::where('code', 'no-membership-check')->sole()->id]);
        $staff = InstitutionStaff::factory()->create(['institution_id' => $competition->institution_id]);
        $registration = app(CompetitionRegistrationService::class)->register($competition, $member);

        return compact('submission', 'competition', 'member', 'staff', 'registration');
    }

    private function registrationPdf(string $text = 'Synthetic document'): UploadedFile
    {
        $bytes = "%PDF-1.4\n";
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 300] /Contents 4 0 R >>'];
        $stream = '% '.$text."\n";
        $objects[] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream';
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($bytes);
            $bytes .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($bytes);
        $bytes .= "xref\n0 5\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $bytes .= sprintf('%010d 00000 n ', $offset)."\n";
        }
        $bytes .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";

        return UploadedFile::fake()->createWithContent('document.pdf', $bytes);
    }
}
