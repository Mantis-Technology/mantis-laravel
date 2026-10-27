<?php

namespace App\Services\PreventiveMaintenance;

use App\Enums\PreventiveFrequencyType;
use App\Enums\PreventivePlanStatus;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\PreventiveMaintenancePlan;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns a preventive maintenance plan (REQ-17) into dated executions.
 *
 * Executions are only generated when this service is called explicitly
 * (never on a GET). Generation is idempotent: a date already taken by an
 * execution of the plan, whatever its status or later postponement, is
 * never created again.
 */
class RecurrenceGenerator
{
    /**
     * How many days ahead of today executions are generated.
     */
    public const HORIZON_DAYS = 90;

    /**
     * Dates on which a recurrence falls inside [$from, $until], bounded by
     * $endsAt when there is one. Pure function: it does not touch the database.
     *
     * Every date is computed from $startsAt, never from the previous
     * occurrence, so month-end clamping does not accumulate (31 Jan, 28 Feb,
     * 31 Mar).
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function occurrences(
        PreventiveFrequencyType $frequency,
        int $interval,
        CarbonInterface $startsAt,
        ?CarbonInterface $endsAt,
        CarbonInterface $from,
        CarbonInterface $until,
    ): Collection {
        $start = CarbonImmutable::instance($startsAt)->startOfDay();
        $first = CarbonImmutable::instance($from)->startOfDay();
        $last = CarbonImmutable::instance($until)->startOfDay();

        if ($endsAt !== null) {
            $end = CarbonImmutable::instance($endsAt)->startOfDay();

            if ($end->lessThan($last)) {
                $last = $end;
            }
        }

        $dates = new Collection;

        if ($frequency === PreventiveFrequencyType::Once) {
            if ($start->greaterThanOrEqualTo($first) && $start->lessThanOrEqualTo($last)) {
                $dates->push($start);
            }

            return $dates;
        }

        $interval = max(1, $interval);

        for ($n = 0; ; $n++) {
            $date = $this->dateAt($start, $frequency, $interval * $n);

            if ($date->greaterThan($last)) {
                break;
            }

            if ($date->greaterThanOrEqualTo($first)) {
                $dates->push($date);
            }
        }

        return $dates;
    }

    /**
     * Creates the executions of an active plan up to the horizon and returns
     * how many were created. A plan whose end date has passed is finished
     * instead; a plan without an end date is never finished here.
     */
    public function generate(PreventiveMaintenancePlan $plan, ?CarbonInterface $today = null): int
    {
        if ($plan->status !== PreventivePlanStatus::Active) {
            return 0;
        }

        $today = CarbonImmutable::instance($today ?? Carbon::today())->startOfDay();

        if ($plan->ends_at !== null && CarbonImmutable::instance($plan->ends_at)->startOfDay()->lessThan($today)) {
            $plan->markAsFinished();

            return 0;
        }

        $taken = PreventiveMaintenanceExecution::query()
            ->where('preventive_maintenance_plan_id', $plan->id)
            ->pluck('original_scheduled_at')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        $created = 0;

        foreach ($this->occurrences(
            $plan->frequency_type,
            $plan->interval,
            $plan->starts_at,
            $plan->ends_at,
            $today,
            $today->addDays(self::HORIZON_DAYS),
        ) as $date) {
            $day = $date->toDateString();

            if ($taken->has($day)) {
                continue;
            }

            try {
                PreventiveMaintenanceExecution::create([
                    'preventive_maintenance_plan_id' => $plan->id,
                    'asset_card_id' => $plan->asset_card_id,
                    'maintenance_category_id' => $plan->maintenance_category_id,
                    'title' => $plan->title,
                    'description' => $plan->description,
                    'instructions' => $plan->instructions,
                    'scheduled_at' => $day,
                    'original_scheduled_at' => $day,
                    'assigned_to_id' => $plan->responsible_id,
                ]);

                $created++;
            } catch (UniqueConstraintViolationException) {
                // Another request created this date first; nothing to do.
            }
        }

        return $created;
    }

    private function dateAt(CarbonImmutable $start, PreventiveFrequencyType $frequency, int $steps): CarbonImmutable
    {
        return match ($frequency) {
            PreventiveFrequencyType::Daily => $start->addDays($steps),
            PreventiveFrequencyType::Weekly => $start->addWeeks($steps),
            PreventiveFrequencyType::Monthly => $start->addMonthsNoOverflow($steps),
            PreventiveFrequencyType::Yearly => $start->addYearsNoOverflow($steps),
            PreventiveFrequencyType::Once => $start,
        };
    }
}
