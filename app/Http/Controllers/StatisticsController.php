<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\StatisticsFilters;
use App\Enums\PreventiveExecutionStatus;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\MaintenanceCategory;
use App\Models\User;
use App\Services\BuildOperationalStatistics;
use App\Services\Predictive\BuildFailureRiskPredictions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Operational statistics of REQ-20: a read-only report of cases, attention
 * times, worked hours, category distribution and technician behaviour.
 */
class StatisticsController extends Controller
{
    public function __construct(
        private readonly BuildOperationalStatistics $statistics,
        private readonly BuildFailureRiskPredictions $predictions,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasAnyRole([
            Role::TENANT_ADMINISTRATOR->value,
            Role::MAINTENANCE_CHIEF->value,
        ])) {
            return Inertia::render('Dashboard');
        }

        $filters = StatisticsFilters::fromRequest($request);

        return Inertia::render('Dashboard', [
            ...$this->statistics->build($filters),
            'filters' => $filters->toArray(),
            'options' => $this->options(),
            'predictive_risk' => $this->predictions->forCurrentTenant(),
            'predictive_risk_url' => route('predictive-risk.show'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'maintenance_categories' => MaintenanceCategory::query()
                ->withInactive()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (MaintenanceCategory $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->values()
                ->all(),
            'asset_cards' => AssetCard::query()
                ->orderBy('code')
                ->get(['id', 'code'])
                ->map(fn (AssetCard $assetCard): array => [
                    'id' => $assetCard->id,
                    'code' => $assetCard->code,
                ])
                ->values()
                ->all(),
            'technicians' => User::query()
                ->role(Role::TECHNICIAN->value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                ])
                ->values()
                ->all(),
            'ticket_statuses' => array_map(
                fn (TicketStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                ],
                TicketStatus::cases(),
            ),
            'preventive_statuses' => array_map(
                fn (PreventiveExecutionStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                ],
                PreventiveExecutionStatus::cases(),
            ),
        ];
    }
}
