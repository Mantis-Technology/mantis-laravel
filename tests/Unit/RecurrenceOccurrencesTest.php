<?php

use App\Enums\PreventiveFrequencyType;
use App\Services\PreventiveMaintenance\RecurrenceGenerator;
use Carbon\CarbonImmutable;

/**
 * @return array<int, string>
 */
function occurrenceDates(
    PreventiveFrequencyType $type,
    int $interval,
    string $startsAt,
    ?string $endsAt,
    string $from,
    string $until,
): array {
    return (new RecurrenceGenerator)
        ->occurrences(
            $type,
            $interval,
            CarbonImmutable::parse($startsAt),
            $endsAt === null ? null : CarbonImmutable::parse($endsAt),
            CarbonImmutable::parse($from),
            CarbonImmutable::parse($until),
        )
        ->map(fn (CarbonImmutable $date) => $date->toDateString())
        ->values()
        ->all();
}

it('repeats daily', function () {
    expect(occurrenceDates(PreventiveFrequencyType::Daily, 1, '2026-01-01', null, '2026-01-01', '2026-01-04'))
        ->toBe(['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04']);
});

it('repeats weekly with an interval of two', function () {
    expect(occurrenceDates(PreventiveFrequencyType::Weekly, 2, '2026-01-01', null, '2026-01-01', '2026-02-01'))
        ->toBe(['2026-01-01', '2026-01-15', '2026-01-29']);
});

it('clamps month ends without accumulating the clamp', function () {
    expect(occurrenceDates(PreventiveFrequencyType::Monthly, 1, '2026-01-31', null, '2026-01-01', '2026-04-30'))
        ->toBe(['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30']);
});

it('repeats monthly with an interval of three', function () {
    expect(occurrenceDates(PreventiveFrequencyType::Monthly, 3, '2026-01-15', null, '2026-01-01', '2026-12-31'))
        ->toBe(['2026-01-15', '2026-04-15', '2026-07-15', '2026-10-15']);
});

it('moves a 29 February yearly plan to 28 February in non leap years', function () {
    expect(occurrenceDates(PreventiveFrequencyType::Yearly, 1, '2024-02-29', null, '2024-01-01', '2028-12-31'))
        ->toBe(['2024-02-29', '2025-02-28', '2026-02-28', '2027-02-28', '2028-02-29']);
});

it('produces a single date for a one time plan, only when inside the window', function () {
    expect(occurrenceDates(PreventiveFrequencyType::Once, 1, '2026-02-10', null, '2026-01-01', '2026-03-01'))
        ->toBe(['2026-02-10']);

    expect(occurrenceDates(PreventiveFrequencyType::Once, 1, '2026-02-10', null, '2026-03-01', '2026-04-01'))
        ->toBe([]);
});

it('stops at the end date of the plan', function () {
    expect(occurrenceDates(PreventiveFrequencyType::Daily, 1, '2026-01-01', '2026-01-03', '2026-01-01', '2026-01-31'))
        ->toBe(['2026-01-01', '2026-01-02', '2026-01-03']);
});

it('skips the dates before the start of the window', function () {
    expect(occurrenceDates(PreventiveFrequencyType::Daily, 1, '2026-01-01', null, '2026-01-10', '2026-01-12'))
        ->toBe(['2026-01-10', '2026-01-11', '2026-01-12']);
});
