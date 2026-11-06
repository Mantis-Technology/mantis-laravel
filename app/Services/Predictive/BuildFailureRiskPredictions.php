<?php

declare(strict_types=1);

namespace App\Services\Predictive;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Enums\PreventiveExecutionStatus;
use App\Models\AssetCard;
use App\Models\MaintenanceCategory;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\Ticket;
use App\Models\TicketClosure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Scores the tenant's assets with the exported LogisticRegression model.
 *
 * The model artifact is a portable JSON representation of the sklearn
 * pipeline (imputers, scaler, one-hot encoder and coefficients) because the
 * Laravel runtime does not execute Python. The result is cached by tenant and
 * by a data-version fingerprint, so the dashboard can poll every minute
 * without recomputing when nothing changed.
 */
final class BuildFailureRiskPredictions
{
    private const MODEL_PATH = 'models/failure_risk_30d.json';

    private const CACHE_MINUTES = 5;

    private const HIGH_RISK = 0.6;

    private const MEDIUM_RISK = 0.4;

    private const TOP_ASSETS = 8;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $model = null;

    /**
     * @return array<string, mixed>
     */
    public function forCurrentTenant(): array
    {
        $tenantId = (string) tenant('id');
        $cutoff = Carbon::today();
        $version = $this->dataVersion();

        /** @var array<string, mixed> $predictions */
        $predictions = Cache::store('database')->remember(
            "predictive-risk:{$tenantId}:{$cutoff->toDateString()}:{$version}",
            now()->addMinutes(self::CACHE_MINUTES),
            fn (): array => $this->build($cutoff),
        );

        return $predictions;
    }

    /**
     * @return array<string, mixed>
     */
    private function build(Carbon $cutoff): array
    {
        $model = $this->model();
        $assets = AssetCard::query()
            ->orderBy('code')
            ->get(['id', 'code', 'data', 'created_at']);

        if ($assets->isEmpty()) {
            return $this->emptyResult($cutoff, $model);
        }

        $tickets = Ticket::query()
            ->where('created_at', '<', $cutoff)
            ->get(['id', 'asset_card_id', 'maintenance_category_id', 'maintenance_type', 'priority', 'created_at']);

        $closures = $tickets->isEmpty()
            ? collect()
            : TicketClosure::query()
                ->whereIn('ticket_id', $tickets->pluck('id'))
                ->get(['ticket_id', 'closed_at'])
                ->keyBy('ticket_id');

        $preventiveExecutions = PreventiveMaintenanceExecution::query()
            ->where('status', PreventiveExecutionStatus::Completed)
            ->whereNotNull('completed_at')
            ->where('completed_at', '<', $cutoff)
            ->get(['asset_card_id', 'completed_at']);

        /** @var array<int, string> $categoryNames */
        $categoryNames = MaintenanceCategory::query()
            ->pluck('name', 'id')
            ->all();

        $ticketsByAsset = $tickets->groupBy('asset_card_id');
        $preventiveByAsset = $preventiveExecutions->groupBy('asset_card_id');

        $scored = [];

        foreach ($assets as $asset) {
            $assetTickets = $ticketsByAsset->get($asset->id, collect());
            $assetPreventive = $preventiveByAsset->get($asset->id, collect());

            $features = $this->features(
                $asset,
                $assetTickets,
                $assetPreventive,
                $closures,
                $categoryNames,
                $cutoff,
            );

            $probability = $this->probability($features, $model);

            $scored[] = [
                'asset_id' => $asset->id,
                'code' => $asset->code,
                'type' => $features['tipo_activo'],
                'site' => $features['sede'],
                'probability' => round($probability, 4),
                'risk' => $this->riskLabel($probability),
                'risk_color' => $this->riskColor($probability),
            ];
        }

        usort(
            $scored,
            fn (array $left, array $right): int => $right['probability'] <=> $left['probability'],
        );

        $probabilities = array_column($scored, 'probability');
        $high = count(array_filter($probabilities, fn (float $value): bool => $value >= self::HIGH_RISK));
        $medium = count(array_filter(
            $probabilities,
            fn (float $value): bool => $value >= self::MEDIUM_RISK && $value < self::HIGH_RISK,
        ));

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'cutoff' => $cutoff->toDateString(),
            'window_days' => 30,
            'model' => $model['model_name'],
            'threshold' => $model['threshold'],
            'summary' => [
                'assets' => count($scored),
                'high' => $high,
                'medium' => $medium,
                'low' => count($scored) - $high - $medium,
                'average_probability' => round(array_sum($probabilities) / count($probabilities), 4),
            ],
            'assets' => array_slice($scored, 0, self::TOP_ASSETS),
        ];
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  Collection<int, PreventiveMaintenanceExecution>  $preventive
     * @param  Collection<int, TicketClosure>  $closures
     * @param  array<int, string>  $categoryNames
     * @return array<string, mixed>
     */
    private function features(
        AssetCard $asset,
        Collection $tickets,
        Collection $preventive,
        Collection $closures,
        array $categoryNames,
        Carbon $cutoff,
    ): array {
        $allTickets = $tickets->sortBy('created_at')->values();

        $tickets = $allTickets
            ->filter(
                fn (Ticket $ticket): bool => $ticket->maintenance_type === MaintenanceType::Corrective,
            )
            ->values();

        $recent = $tickets->filter(
            fn (Ticket $ticket): bool => $ticket->created_at >= $cutoff->copy()->subDays(180),
        );

        $failureDates = $tickets
            ->pluck('created_at')
            ->filter()
            ->sort()
            ->values();

        $lastFailure = $failureDates->last();
        $lastPreventive = $preventive->pluck('completed_at')->filter()->sort()->last();
        $lastClosure = $allTickets
            ->map(fn (Ticket $ticket): mixed => $closures->get($ticket->id)?->closed_at)
            ->filter(fn (mixed $closedAt): bool => $closedAt !== null && $closedAt < $cutoff)
            ->sort()
            ->last();

        $maintenanceCandidates = collect([
            [$lastPreventive, 'preventivo'],
            [$lastClosure, 'correctivo'],
        ])->filter(fn (array $candidate): bool => $candidate[0] !== null)
            ->sortBy(fn (array $candidate): int => $candidate[0]->timestamp)
            ->last();

        $priorities = $recent
            ->pluck('priority')
            ->filter()
            ->map(fn (MaintenancePriority $priority): int => match ($priority) {
                MaintenancePriority::Low => 1,
                MaintenancePriority::Medium => 2,
                MaintenancePriority::High => 3,
                MaintenancePriority::Critical => 4,
            });

        $resolutions = $recent
            ->map(function (Ticket $ticket) use ($closures, $cutoff): ?float {
                $closedAt = $closures->get($ticket->id)?->closed_at;

                if ($closedAt === null || $closedAt >= $cutoff) {
                    return null;
                }

                return max(0, $ticket->created_at->diffInMinutes($closedAt, false) / 60);
            })
            ->filter(fn (mixed $value): bool => $value !== null);

        $lastCategoryId = $tickets->last()?->maintenance_category_id;
        $lastCategory = $lastCategoryId === null ? null : ($categoryNames[$lastCategoryId] ?? null);

        $mtbf = null;
        if ($failureDates->count() >= 2) {
            $intervals = [];
            for ($index = 1; $index < $failureDates->count(); $index++) {
                $intervals[] = $failureDates[$index - 1]->diffInDays($failureDates[$index], true);
            }

            $mtbf = array_sum($intervals) / count($intervals);
        }

        $installedAt = $this->assetField($asset, 'fecha_instalacion');
        $installedDate = $installedAt === null
            ? ($asset->created_at ?? $cutoff)
            : Carbon::parse($installedAt);

        return [
            'antiguedad_dias' => max(0, (int) $installedDate->diffInDays($cutoff, true)),
            'fallas_90d' => $tickets->filter(
                fn (Ticket $ticket): bool => $ticket->created_at >= $cutoff->copy()->subDays(90),
            )->count(),
            'fallas_180d' => $recent->count(),
            'dias_desde_ultimo_mantenimiento' => $maintenanceCandidates === null
                ? null
                : (int) $maintenanceCandidates[0]->diffInDays($cutoff, true),
            'tipo_ultimo_mantenimiento' => $maintenanceCandidates === null
                ? null
                : $maintenanceCandidates[1],
            'dias_desde_ultima_falla' => $lastFailure === null
                ? null
                : (int) $lastFailure->diffInDays($cutoff, true),
            'mtbf_dias' => $mtbf,
            'prioridad_promedio' => $priorities->isEmpty() ? null : $priorities->average(),
            'resolucion_promedio_h' => $resolutions->isEmpty() ? null : $resolutions->average(),
            'causa_ultima_falla' => $lastCategory,
            'tipo_activo' => $this->assetField($asset, 'tipo_activo'),
            'sede' => $this->assetField($asset, 'sede'),
            'criticidad' => $this->assetField($asset, 'criticidad'),
        ];
    }

    /**
     * @param  array<string, mixed>  $features
     * @param  array<string, mixed>  $model
     */
    private function probability(array $features, array $model): float
    {
        $values = [];

        foreach ($model['numeric_features'] as $feature) {
            $raw = $features[$feature] ?? null;
            $value = is_numeric($raw) ? (float) $raw : (float) $model['numeric_impute'][$feature];
            $values["numeric__{$feature}"] = ($value - (float) $model['numeric_mean'][$feature])
                / (float) $model['numeric_scale'][$feature];
        }

        foreach ($model['categorical_features'] as $feature) {
            $raw = $features[$feature] ?? null;
            $value = is_string($raw) && trim($raw) !== ''
                ? $raw
                : (string) $model['categorical_impute'][$feature];

            foreach ($model['categories'][$feature] as $category) {
                $values["categorical__{$feature}_{$category}"] = $value === $category ? 1.0 : 0.0;
            }
        }

        $score = (float) $model['intercept'];

        foreach ($model['feature_names'] as $index => $featureName) {
            $score += (float) $model['coefficients'][$index] * ($values[$featureName] ?? 0.0);
        }

        if ($score >= 0) {
            return 1 / (1 + exp(-$score));
        }

        $exponential = exp($score);

        return $exponential / (1 + $exponential);
    }

    private function assetField(AssetCard $asset, string $field): ?string
    {
        /** @var array<string, mixed>|null $rawData */
        $rawData = $asset->data;
        $data = is_array($rawData) ? $rawData : [];

        foreach ($data as $section) {
            if (! is_array($section) || ! array_key_exists($field, $section)) {
                continue;
            }

            $payload = $section[$field];
            $value = is_array($payload) ? ($payload['value'] ?? null) : $payload;

            if (is_scalar($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function dataVersion(): string
    {
        $values = [
            Ticket::query()->max('updated_at'),
            PreventiveMaintenanceExecution::query()->max('updated_at'),
            TicketClosure::query()->max('closed_at'),
            AssetCard::query()->max('updated_at'),
        ];

        return implode('|', array_map(
            fn (mixed $value): string => is_scalar($value) ? (string) $value : '0',
            $values,
        ));
    }

    /**
     * @param  array<string, mixed>  $model
     * @return array<string, mixed>
     */
    private function emptyResult(Carbon $cutoff, array $model): array
    {
        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'cutoff' => $cutoff->toDateString(),
            'window_days' => 30,
            'model' => $model['model_name'],
            'threshold' => $model['threshold'],
            'summary' => [
                'assets' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
                'average_probability' => 0.0,
            ],
            'assets' => [],
        ];
    }

    private function riskLabel(float $probability): string
    {
        return match (true) {
            $probability >= self::HIGH_RISK => 'Alto',
            $probability >= self::MEDIUM_RISK => 'Medio',
            default => 'Bajo',
        };
    }

    private function riskColor(float $probability): string
    {
        return match (true) {
            $probability >= self::HIGH_RISK => 'danger',
            $probability >= self::MEDIUM_RISK => 'warning',
            default => 'success',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function model(): array
    {
        if ($this->model !== null) {
            return $this->model;
        }

        $path = base_path(self::MODEL_PATH);

        if (! is_file($path)) {
            throw new RuntimeException("No se encontró el modelo predictivo exportado en [{$path}].");
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('El modelo predictivo exportado no contiene un JSON válido.');
        }

        /** @var array<string, mixed> $decoded */
        $this->model = $decoded;

        return $this->model;
    }
}
