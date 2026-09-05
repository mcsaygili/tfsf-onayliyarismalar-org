<?php

namespace Tests\Feature;

use App\Models\MailSetting;
use App\Models\PortfolioSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SingletonSettingsTest extends TestCase
{
    use RefreshDatabase;

    public static function settings(): array
    {
        return [
            'mail' => [MailSetting::class, 'mail_settings', 'daily_quota', 500],
            'portfolio' => [PortfolioSetting::class, 'portfolio_settings', 'max_photos_per_user', 2],
        ];
    }

    #[DataProvider('settings')]
    public function test_settings_retain_their_identity_after_the_sequence_advances(string $model, string $table, string $field, int $value): void
    {
        DB::table($table)->insert(['id' => 42]);
        DB::table($table)->where('id', 42)->delete();

        $settings = $model::current();
        $this->assertSame(1, $settings->id);
        $settings->update([$field => $value]);

        $again = $model::current();
        $this->assertSame(1, $again->id);
        $this->assertSame($value, $again->{$field});
        $this->assertDatabaseCount($table, 1);
    }
}
