<?php

namespace Tests\Feature\Eys;

use App\Services\ResultPublicationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesResultSelection;
use Tests\TestCase;

class PrivateResultAssetMigrationTest extends TestCase
{
    use CreatesResultSelection, DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Migration rollback with data is verified on MariaDB.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_rollback_removes_private_mappings_before_old_code_can_publish_them(): void
    {
        $f = $this->resultFixture();
        $f['result']->awards()->create(['competition_category_award_id' => $f['award']->id, 'slot_number' => 1]);
        $publication = app(ResultPublicationService::class)->create($f['competition'], $f['round'], $f['reviewer']);
        $private = $publication->assets()->where('is_public', false)->sole();
        $public = $publication->assets()->where('is_public', true)->sole();
        $snapshot = DB::table('competition_result_publications')->where('id', $publication->id)->value('snapshot');
        $migration = require database_path('migrations/2026_09_05_230000_scope_private_result_assets.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('competition_result_assets', 'is_public'));
        $this->assertDatabaseMissing('competition_result_assets', ['id' => $private->id]);
        $this->assertDatabaseHas('competition_result_assets', ['id' => $public->id]);
        $this->assertTrue(Storage::disk('local')->exists($private->disk_path));
        $migration->up();
        $this->assertTrue($public->fresh()->is_public);
        $this->assertNull($public->fresh()->owner_user_id);
        $this->assertSame($snapshot, DB::table('competition_result_publications')->where('id', $publication->id)->value('snapshot'));
        $this->assertDatabaseCount('competition_result_assets', 1);
    }
}
