<?php

declare(strict_types=1);

namespace App\Http\Controllers\tickets;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\AssetCard;
use App\Models\Location;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * Shows the incident report form. When the request comes from an asset's
     * QR code (?asset=CODE), the asset is resolved automatically and locked
     * in the form; otherwise the operator picks it from the list.
     */
    public function create(Request $request): Response
    {
        $assetCard = $this->assetCardFromRequest($request);

        return Inertia::render('Tickets/Create/index', [
            'assetCard' => $assetCard
                ? [
                    'id' => $assetCard->id,
                    'code' => $assetCard->code,
                ]
                : null,
            'assetCards' => $assetCard ? [] : $this->assetCards(),
            'locations' => $this->locations(),
            'action' => route('tickets.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_card_id' => ['required', 'integer', 'exists:asset_cards,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        Ticket::query()->create([
            'asset_card_id' => (int) $validated['asset_card_id'],
            'reported_by' => $request->user()->id,
            'location_id' => $validated['location_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => TicketStatus::Reported,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Incidencia reportada correctamente.');
    }

    /**
     * Resolves the asset card from the ?asset= query parameter (the asset
     * code embedded in the QR code). Aborts with 404 when the code does not
     * match any asset.
     */
    private function assetCardFromRequest(Request $request): ?AssetCard
    {
        $code = $request->query('asset');

        if (! is_string($code) || $code === '') {
            return null;
        }

        $assetCard = AssetCard::query()->where('code', $code)->first();

        abort_unless($assetCard instanceof AssetCard, 404);

        return $assetCard;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assetCards(): array
    {
        return AssetCard::query()
            ->orderBy('code')
            ->get(['id', 'code'])
            ->map(fn (AssetCard $assetCard): array => [
                'id' => $assetCard->id,
                'code' => $assetCard->code,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function locations(): array
    {
        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $childrenMap = $locations->groupBy(
            fn (Location $location) => $location->parent_id ?? 0
        );

        $build = function (array $parents) use (&$build, $childrenMap): array {
            $nodes = [];

            foreach ($parents as $location) {
                $nodes[] = [
                    'id' => $location->id,
                    'name' => $location->name,
                    'children' => $build(
                        $childrenMap->get($location->id, new Collection)->all()
                    ),
                ];
            }

            return $nodes;
        };

        return $build($childrenMap->get(0, new Collection)->all());
    }
}
