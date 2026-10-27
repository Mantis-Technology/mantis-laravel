<?php

namespace Database\Factories;

use App\Enums\PreventiveExecutionStatus;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\PreventiveMaintenancePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PreventiveMaintenanceExecution>
 */
class PreventiveMaintenanceExecutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Does not provide `asset_card_id`, `maintenance_category_id` nor
     * `assigned_to_id` (see PreventiveMaintenancePlanFactory). Use
     * `forPlan()` to copy them from a plan. `original_scheduled_at` is
     * filled by the model from `scheduled_at`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'instructions' => fake()->paragraph(),
            'scheduled_at' => Carbon::today(),
            'status' => PreventiveExecutionStatus::Scheduled,
        ];
    }

    public function forPlan(PreventiveMaintenancePlan $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'preventive_maintenance_plan_id' => $plan->id,
            'asset_card_id' => $plan->asset_card_id,
            'maintenance_category_id' => $plan->maintenance_category_id,
            'assigned_to_id' => $plan->responsible_id,
            'title' => $plan->title,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreventiveExecutionStatus::InProgress,
            'started_at' => Carbon::now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreventiveExecutionStatus::Completed,
            'started_at' => Carbon::now()->subHour(),
            'completed_at' => Carbon::now(),
            'completed_by_id' => User::factory(),
            'execution_notes' => 'Trabajo realizado.',
        ]);
    }

    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreventiveExecutionStatus::Skipped,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreventiveExecutionStatus::Cancelled,
        ]);
    }
}
