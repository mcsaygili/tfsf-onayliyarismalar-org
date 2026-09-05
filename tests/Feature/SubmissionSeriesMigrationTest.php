<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesEvaluationRevision;
use Tests\TestCase;

class SubmissionSeriesMigrationTest extends TestCase
{
    use CreatesEvaluationRevision, DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Migration rollback with data is verified on MariaDB.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_existing_submissions_receive_valid_codes_without_changing_entries_or_photos(): void
    {
        $f = $this->evaluationFixture();
        $before = DB::table('competition_submissions')->orderBy('id')->get()->map(fn ($row) => collect($row)->except('series_code')->all())->all();
        $photos = DB::table('competition_submission_photos')->orderBy('id')->get()->toJson();
        $migration = require database_path('migrations/2026_09_05_260000_group_submission_photos_as_series.php');
        $migration->down();
        $migration->up();
        $after = DB::table('competition_submissions')->orderBy('id')->get();
        $this->assertSame($before, $after->map(fn ($row) => collect($row)->except('series_code')->all())->all());
        $this->assertSame($photos, DB::table('competition_submission_photos')->orderBy('id')->get()->toJson());
        foreach ($after as $row) {
            $this->assertMatchesRegularExpression('/^[A-F0-9]{16}$/', $row->series_code);
        }
        $this->assertFalse($f['submission']->category->fresh()->photos_grouped);
    }
}
