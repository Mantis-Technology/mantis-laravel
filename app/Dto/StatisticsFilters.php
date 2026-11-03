<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\PreventiveExecutionStatus;
use App\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Filters applied to the operational statistics (REQ-20). Invalid values are
 * ignored so a malformed query string never breaks the report.
 */
final readonly class StatisticsFilters
{
    public function __construct(
        public ?Carbon $dateFrom = null,
        public ?Carbon $dateTo = null,
        public ?int $maintenanceCategoryId = null,
        public ?int $assetCardId = null,
        public ?int $technicianId = null,
        public ?TicketStatus $ticketStatus = null,
        public ?PreventiveExecutionStatus $preventiveStatus = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $dateFrom = self::date($request->query('date_from'));
        $dateTo = self::date($request->query('date_to'));

        if ($dateFrom !== null && $dateTo !== null && $dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return new self(
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            maintenanceCategoryId: self::integer($request->query('maintenance_category_id')),
            assetCardId: self::integer($request->query('asset_card_id')),
            technicianId: self::integer($request->query('technician_id')),
            ticketStatus: TicketStatus::tryFrom(self::string($request->query('status')) ?? ''),
            preventiveStatus: PreventiveExecutionStatus::tryFrom(self::string($request->query('preventive_status')) ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date_from' => $this->dateFrom?->toDateString(),
            'date_to' => $this->dateTo?->toDateString(),
            'maintenance_category_id' => $this->maintenanceCategoryId,
            'asset_card_id' => $this->assetCardId,
            'technician_id' => $this->technicianId,
            'status' => $this->ticketStatus?->value,
            'preventive_status' => $this->preventiveStatus?->value,
        ];
    }

    private static function date(mixed $value): ?Carbon
    {
        $value = self::string($value);

        if ($value === null || ! Carbon::hasFormat($value, 'Y-m-d')) {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }

    private static function integer(mixed $value): ?int
    {
        $value = self::string($value);

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }

    private static function string(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
