<?php

namespace Database\Seeders;

use App\Models\ParticipantApprovalProcess;
use Illuminate\Database\Seeder;

class ParticipantApprovalProcessSeeder extends Seeder
{
    private const PROCESSES = [
        [
            'code' => 'representative',
            'sort_order' => 10,
            'tr' => [
                'name' => 'Temsilci',
                'description' => 'Katılımcı başvuruları, yarışmaya atanan TFSF temsilcisi tarafından incelenir ve onaylanır.',
            ],
            'en' => [
                'name' => 'Representative',
                'description' => 'Participant applications are reviewed and approved by the TFSF representative assigned to the competition.',
            ],
        ],
        [
            'code' => 'institution',
            'sort_order' => 20,
            'tr' => [
                'name' => 'Kurum',
                'description' => 'Katılımcı başvuruları, yarışmayı düzenleyen kurumun yetkilileri tarafından incelenir ve onaylanır.',
            ],
            'en' => [
                'name' => 'Institution',
                'description' => 'Participant applications are reviewed and approved by authorized staff of the organizing institution.',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::PROCESSES as $item) {
            $process = ParticipantApprovalProcess::withTrashed()->firstOrNew(['code' => $item['code']]);
            $process->fill(['sort_order' => $item['sort_order'], 'status' => true]);
            $process->save();

            if ($process->trashed()) {
                $process->restore();
            }

            $process->upsertTranslations([
                'tr' => $item['tr'],
                'en' => $item['en'],
            ]);
        }
    }
}
