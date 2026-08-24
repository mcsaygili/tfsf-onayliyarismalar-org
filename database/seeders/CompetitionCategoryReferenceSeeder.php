<?php

namespace Database\Seeders;

use App\Models\AgeEligibilityRule;
use App\Models\CaptureDevice;
use App\Models\MemberGroup;
use App\Models\ParticipantGender;
use App\Models\ProcessingMethod;
use Illuminate\Database\Seeder;

class CompetitionCategoryReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $legacyGender = ParticipantGender::withTrashed()->where('code', 'unspecified')->first();
        if ($legacyGender && ! ParticipantGender::withTrashed()->where('code', 'no-check')->exists()) {
            $legacyGender->update(['code' => 'no-check']);
        }

        $this->seed(ParticipantGender::class, [
            ['male', 10, 'Erkek', 'Male', 'Yalnızca erkek katılımcılar kabul edilir.', 'Only male participants are eligible.'],
            ['female', 20, 'Kadın', 'Female', 'Yalnızca kadın katılımcılar kabul edilir.', 'Only female participants are eligible.'],
            ['no-check', 30, 'Cinsiyet Kontrolü Yok', 'No Gender Check', 'Katılım sırasında cinsiyet bilgisi kontrol edilmez.', 'Gender information is not checked during entry.'],
        ]);

        $this->seedAgeRules();

        $this->seed(MemberGroup::class, [
            ['no-membership-check', 5, 'Üyelik Kontrolü Yok', 'No Membership Check', 'Katılım sırasında üyelik grubu kontrol edilmez.', 'Membership group is not checked during entry.'],
            ['0', 10, 'Üye', 'Member', 'TFSF üyesi kullanıcılar.', 'TFSF member users.'],
            ['1', 20, 'Dernek Üyesi', 'Association Member', 'Bir fotoğraf derneğine bağlı kullanıcılar.', 'Users affiliated with a photography association.'],
            ['2', 30, 'Dernek Alt Üyesi', 'Association Sub-member', 'Dernek alt üyeliği bulunan kullanıcılar.', 'Users holding an association sub-membership.'],
            ['3', 40, 'Kayıtlı Katılımcı', 'Registered Participant', 'Sisteme kayıtlı katılımcı kullanıcılar.', 'Participant users registered in the system.'],
        ]);

        $this->seed(CaptureDevice::class, [
            ['no-device-check', 5, 'Cihaz Kontrolü Yok', 'No Device Check', 'Fotoğrafın çekildiği cihaz türü kontrol edilmez.', 'The capture device type is not checked.'],
            ['camera', 10, 'Fotoğraf Makinesi', 'Camera', 'Dijital veya analog fotoğraf makinesiyle üretilen fotoğraflar.', 'Photographs produced with a digital or analogue camera.'],
            ['mobile-device', 20, 'Cep Telefonu veya Tablet', 'Mobile Phone or Tablet', 'Mobil cihaz kamerasıyla üretilen fotoğraflar.', 'Photographs produced with a mobile device camera.'],
            ['drone', 30, 'Drone', 'Drone', 'İnsansız hava aracı kamerasıyla üretilen fotoğraflar.', 'Photographs produced with an unmanned aerial vehicle camera.'],
        ]);

        $softwareDevice = CaptureDevice::withTrashed()->where('code', 'software')->first();
        $softwareDevice?->update(['status' => false, 'is_system' => true]);

        $this->seed(ProcessingMethod::class, [
            ['no-processing-check', 5, 'Düzenleme Kontrolü Yok', 'No Processing Check', 'Fotoğrafın düzenlenme yöntemi kontrol edilmez.', 'The image-processing method is not checked.'],
            ['photo-editing-software', 10, 'Fotoğraf Düzenleme Uygulaması', 'Photo-editing Application', 'Photoshop, GIMP ve benzeri fotoğraf düzenleme uygulamalarıyla işlenen görseller.', 'Images processed using Photoshop, GIMP, or similar photo-editing applications.'],
        ]);
    }

    private function seedAgeRules(): void
    {
        $rules = [
            ['no-age-check', null, null, true, true, 10, 'Yaş Kontrolü Yok', 'No Age Check', 'Katılımcının yaşı kontrol edilmez.', 'The participant’s age is not checked.'],
            ['zero-to-18', 0, 18, true, true, 20, '0–18 Yaş Arası', 'Ages 0–18', 'Yarışma sonlanma tarihinde 18 yaşında veya daha küçük katılımcılar kabul edilir.', 'Participants aged 18 or younger on the competition end date are eligible.'],
            ['zero-to-21', 0, 21, true, true, 30, '0–21 Yaş Arası', 'Ages 0–21', 'Yarışma sonlanma tarihinde 21 yaşında veya daha küçük katılımcılar kabul edilir.', 'Participants aged 21 or younger on the competition end date are eligible.'],
            ['under-18', 0, 18, true, false, 40, '18 Yaş Altı Katılımcı', 'Participants Under 18', 'Yarışma sonlanma tarihinde henüz 18 yaşını doldurmamış katılımcılar kabul edilir.', 'Participants who have not yet turned 18 on the competition end date are eligible.'],
            ['18-and-over', 18, null, true, true, 50, '18 Yaş ve Üstü Katılımcı', 'Participants Aged 18 and Over', 'Yarışma sonlanma tarihinde 18 yaşını doldurmuş katılımcılar kabul edilir.', 'Participants aged 18 or older on the competition end date are eligible.'],
        ];

        foreach ($rules as [$code, $minimum, $maximum, $minimumInclusive, $maximumInclusive, $sortOrder, $trName, $enName, $trDescription, $enDescription]) {
            $rule = AgeEligibilityRule::withTrashed()->firstOrNew(['code' => $code]);
            $rule->fill(['minimum_age' => $minimum, 'maximum_age' => $maximum, 'minimum_inclusive' => $minimumInclusive, 'maximum_inclusive' => $maximumInclusive, 'sort_order' => $sortOrder, 'status' => true, 'is_system' => true]);
            $rule->save();
            if ($rule->trashed()) {
                $rule->restore();
            }
            $rule->upsertTranslations(['tr' => ['name' => $trName, 'description' => $trDescription], 'en' => ['name' => $enName, 'description' => $enDescription]]);
        }
    }

    /** @param class-string<ParticipantGender|MemberGroup|CaptureDevice|ProcessingMethod> $model */
    private function seed(string $model, array $items): void
    {
        foreach ($items as [$code, $sortOrder, $trName, $enName, $trDescription, $enDescription]) {
            $reference = $model::withTrashed()->firstOrNew(['code' => $code]);
            $reference->fill(['sort_order' => $sortOrder, 'status' => true, 'is_system' => true]);
            $reference->save();

            if ($reference->trashed()) {
                $reference->restore();
            }

            $reference->upsertTranslations([
                'tr' => ['name' => $trName, 'description' => $trDescription],
                'en' => ['name' => $enName, 'description' => $enDescription],
            ]);
        }
    }
}
