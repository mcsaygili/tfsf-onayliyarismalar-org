<?php

namespace Database\Seeders;

use App\Models\EvaluationCriterion;
use Illuminate\Database\Seeder;

class EvaluationCriterionSeeder extends Seeder
{
    private const LEGACY_CODES = [
        'technical-quality',
        'composition',
        'originality-creativity',
        'theme-compliance',
        'visual-impact',
    ];

    public function run(): void
    {
        $criterion = EvaluationCriterion::withTrashed()->where('code', 'general-evaluation')->first()
            ?? EvaluationCriterion::withTrashed()->where('code', 'technical-quality')->first()
            ?? new EvaluationCriterion;

        $criterion->fill([
            'code' => 'general-evaluation',
            'default_min_score' => 3,
            'default_max_score' => 9,
            'default_weight' => 1,
            'sort_order' => 10,
            'status' => true,
            'is_system' => true,
        ]);
        $criterion->deleted_at = null;
        $criterion->save();
        $criterion->upsertTranslations([
            'tr' => [
                'name' => 'Genel Değerlendirme',
                'description' => 'Jürinin eseri bütünsel olarak değerlendirdiği, 3 ile 9 arasında puan verdiği mevcut değerlendirme yöntemi.',
            ],
            'en' => [
                'name' => 'General Evaluation',
                'description' => 'The current evaluation method in which the jury assesses the work as a whole and awards a score from 3 to 9.',
            ],
        ]);

        EvaluationCriterion::query()
            ->where('id', '!=', $criterion->id)
            ->whereIn('code', self::LEGACY_CODES)
            ->update(['status' => false]);
    }
}
