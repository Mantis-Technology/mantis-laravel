<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssetCards;

use App\Http\Controllers\Controller;
use App\Models\AssetCard;
use App\Services\BuildAssetTraceability;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * REQ-19: consulta de la trazabilidad de un activo, con filtros por origen
 * (incidencias o preventivos) y por usuario, paginada.
 */
class AssetCardTraceabilityController extends Controller
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly BuildAssetTraceability $traceability,
    ) {}

    public function __invoke(Request $request, AssetCard $assetCard): Response
    {
        $all = $this->traceability->execute($assetCard);

        $source = in_array($request->query('source'), ['ticket', 'preventive'], true)
            ? (string) $request->query('source')
            : null;

        $userId = is_numeric($request->query('user'))
            ? (int) $request->query('user')
            : null;

        $users = $all
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();

        $filtered = $all
            ->when($source !== null, fn ($events) => $events->where('source', $source))
            ->when($userId !== null, fn ($events) => $events->filter(
                fn (array $event): bool => ($event['user']['id'] ?? null) === $userId
            ))
            ->values();

        $total = $filtered->count();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);

        $query = array_filter(
            ['source' => $source, 'user' => $userId],
            fn ($value): bool => $value !== null,
        );

        $pageUrl = fn (int $number): string => $request->url().'?'.http_build_query($query + ['page' => $number]);

        return Inertia::render('AssetCards/Traceability/Index', [
            'assetCard' => [
                'id' => $assetCard->id,
                'code' => $assetCard->code,
            ],
            'events' => $filtered->forPage($page, self::PER_PAGE)->values()->all(),
            'filters' => ['source' => $source, 'user' => $userId],
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'total' => $total,
                'per_page' => self::PER_PAGE,
                'prev_url' => $page > 1 ? $pageUrl($page - 1) : null,
                'next_url' => $page < $lastPage ? $pageUrl($page + 1) : null,
            ],
            'baseUrl' => $request->url(),
            'showUrl' => route('asset-cards.show', $assetCard),
        ]);
    }
}
