<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name')->nullable();
            $table->text('subject')->nullable();
            $table->text('purpose')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'locale']);
        });

        $now = now();
        $defaultLocale = 'tr';

        foreach (DB::table('competitions')->get(['id', 'name', 'subject', 'purpose']) as $competition) {
            if ($competition->name === null && $competition->subject === null && $competition->purpose === null) {
                continue;
            }

            DB::table('competition_translations')->insert([
                'id' => (string) Str::uuid(),
                'competition_id' => $competition->id,
                'locale' => $defaultLocale,
                'name' => $competition->name,
                'subject' => $competition->subject,
                'purpose' => $competition->purpose,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['name', 'subject', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('name')->nullable()->after('audience');
            $table->text('subject')->nullable()->after('partners');
            $table->text('purpose')->nullable()->after('subject');
        });

        $defaultLocale = 'tr';

        foreach (DB::table('competition_translations')->where('locale', $defaultLocale)->get() as $translation) {
            DB::table('competitions')->where('id', $translation->competition_id)->update([
                'name' => $translation->name,
                'subject' => $translation->subject,
                'purpose' => $translation->purpose,
            ]);
        }

        Schema::dropIfExists('competition_translations');
    }
};
