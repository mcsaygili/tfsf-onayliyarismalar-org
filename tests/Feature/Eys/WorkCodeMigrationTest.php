<?php

namespace Tests\Feature\Eys;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesEvaluationRevision;
use Tests\TestCase;

class WorkCodeMigrationTest extends TestCase
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

    public function test_migration_backfills_existing_photos_without_changing_their_content(): void
    {
        $f = $this->evaluationFixture();
        $copy = $f['photo']->replicate();
        $copy->sha256 = hash('sha256', 'migration-copy');
        $copy->save();
        $before = DB::table('competition_submission_photos')->orderBy('id')->get()->map(fn ($photo) => collect($photo)->except('anonymous_code')->all())->all();
        $migration = require database_path('migrations/2026_09_05_210000_identify_submission_photos_anonymously.php');
        $migration->down();
        $migration->up();
        $after = DB::table('competition_submission_photos')->orderBy('id')->get();
        $this->assertSame($before, $after->map(fn ($photo) => collect($photo)->except('anonymous_code')->all())->all());
        $this->assertCount(2, $after->pluck('anonymous_code')->unique());
        foreach ($after as $photo) {
            $this->assertMatchesRegularExpression('/^[A-F0-9]{16}$/', $photo->anonymous_code);
        }
    }
}
