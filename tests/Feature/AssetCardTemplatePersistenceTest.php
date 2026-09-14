<?php

use App\Models\CardTemplateVersion;
use App\Services\CreateAssetCardTemplate;
use App\Services\CreateAssetCardTemplateVersion;
use Illuminate\Support\Facades\DB;

test('persists template versions on a database without an id column', function () {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL.');
    }

    $schema = 'template_persistence_check';

    config([
        'database.connections.template_check' => array_merge(
            config('database.connections.pgsql'),
            ['search_path' => $schema],
        ),
    ]);

    DB::connection('template_check')->statement(
        "DROP SCHEMA IF EXISTS \"{$schema}\" CASCADE",
    );
    DB::connection('template_check')->statement(
        "CREATE SCHEMA \"{$schema}\"",
    );

    try {
        $this->artisan('migrate', [
            '--database' => 'template_check',
            '--path' => 'database/migrations/tenant',
        ])->assertSuccessful();

        config(['database.default' => 'template_check']);

        $template = app(CreateAssetCardTemplate::class)->execute(
            'Equipo de cómputo',
            'Demo',
        );

        expect($template->version)->toBe(1)
            ->and(
                CardTemplateVersion::query()
                    ->where('asset_card_template_id', $template->id)
                    ->where('version', 1)
                    ->exists(),
            )->toBeTrue();

        $newVersion = app(CreateAssetCardTemplateVersion::class)->execute(
            $template,
        );

        expect($newVersion)->toBe(2)
            ->and($template->fresh()?->version)->toBe(2)
            ->and(
                CardTemplateVersion::query()
                    ->where('asset_card_template_id', $template->id)
                    ->count(),
            )->toBe(2);
    } finally {
        config(['database.default' => 'pgsql']);

        DB::connection('template_check')->statement(
            "DROP SCHEMA IF EXISTS \"{$schema}\" CASCADE",
        );
    }
});
