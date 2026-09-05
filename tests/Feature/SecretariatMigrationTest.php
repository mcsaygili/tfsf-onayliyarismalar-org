<?php

namespace Tests\Feature;

use App\Services\PanelAccountAccess;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesSecretariat;
use Tests\TestCase;

class SecretariatMigrationTest extends TestCase
{
    use CreatesSecretariat, DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Data-preserving rollback requires MariaDB.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_rollback_preserves_unbound_accounts_and_reapplication_does_not_recreate_assignments(): void
    {
        $f = $this->secretariatFixture();
        $id = $f['secretariat']->id;
        $email = $f['secretariat']->email;
        $migration = require database_path('migrations/2026_09_05_280000_restore_independent_secretariat_accounts.php');
        $migration->down();
        $this->assertDatabaseHas('institution_staff', ['id' => $id, 'email' => $email, 'institution_id' => null]);
        $migration->up();
        $this->assertTrue($f['secretariat']->fresh()->isSecretariat());
        $this->assertNull($f['competition']->fresh()->secretariat_id);
        $this->assertSame('institution', $f['staff']->fresh()->account_kind);
        $this->assertSame($f['competition']->institution_id, $f['staff']->fresh()->institution_id);
        $this->assertNull(app(PanelAccountAccess::class)->denialReason($f['secretariat']->fresh()));
    }
}
