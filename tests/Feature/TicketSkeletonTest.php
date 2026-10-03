<?php

use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    config([
        'database.default' => 'tenant_test',
        'database.connections.tenant_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    $this->artisan('migrate', [
        '--database' => 'tenant_test',
        '--path' => 'database/migrations/tenant',
    ])->assertSuccessful();

    $template = AssetCardTemplate::query()->create([
        'name' => 'Equipo de cómputo',
        'version' => 1,
    ]);

    CardTemplateVersion::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
    ]);

    $this->assetCard = AssetCard::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'code' => 'IDS-EC-001',
    ]);
});

test('a new ticket is reported by default and belongs to its asset and reporter', function () {
    $reporter = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $reporter->id,
    ]);

    expect($ticket->status)->toBe(TicketStatus::Reported)
        ->and($ticket->assigned_to)->toBeNull()
        ->and($ticket->assetCard->id)->toBe($this->assetCard->id)
        ->and($ticket->reporter->id)->toBe($reporter->id);
});

test('a ticket can transition forward one step at a time', function () {
    $technician = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'assigned_to' => $technician->id,
    ]);

    expect($ticket->transitionTo(TicketStatus::Categorized))->toBeNull();
    expect($ticket->fresh()->status)->toBe(TicketStatus::Categorized);

    expect($ticket->transitionTo(TicketStatus::Assigned))->toBeNull();
    expect($ticket->fresh()->status)->toBe(TicketStatus::Assigned);
});

test('a ticket cannot skip a status', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    $error = $ticket->transitionTo(TicketStatus::Assigned);

    expect($error)->not->toBeNull();
    expect($ticket->fresh()->status)->toBe(TicketStatus::Reported);
});

test('a ticket cannot transition backwards', function () {
    $ticket = Ticket::factory()->categorized()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    $error = $ticket->transitionTo(TicketStatus::Reported);

    expect($error)->not->toBeNull();
    expect($ticket->fresh()->status)->toBe(TicketStatus::Categorized);
});

test('the assigned factory state sets an assignee', function () {
    $ticket = Ticket::factory()->assigned()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    expect($ticket->status)->toBe(TicketStatus::Assigned)
        ->and($ticket->assignee)->not->toBeNull();
});
