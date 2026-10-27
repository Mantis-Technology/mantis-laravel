<?php

namespace Database\Factories;

use App\Enums\PreventiveFrequencyType;
use App\Enums\PreventivePlanStatus;
use App\Models\PreventiveMaintenancePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PreventiveMaintenancePlan>
 */
class PreventiveMaintenancePlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Does not provide `asset_card_id`, `maintenance_category_id` nor
     * `responsible_id`: `AssetCard` has no factory yet (same as
     * TicketFactory), so each test must pass them explicitly.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'instructions' => fake()->paragraph(),
            'frequency_type' => PreventiveFrequencyType::Monthly,
            'interval' => 1,
            'starts_at' => Carbon::today(),
            'ends_at' => null,
            'created_by_id' => User::factory(),
            'status' => PreventivePlanStatus::Active,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreventivePlanStatus::Paused,
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreventivePlanStatus::Finished,
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency_type' => PreventiveFrequencyType::Daily,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency_type' => PreventiveFrequencyType::Weekly,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency_type' => PreventiveFrequencyType::Monthly,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency_type' => PreventiveFrequencyType::Yearly,
        ]);
    }

    public function once(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency_type' => PreventiveFrequencyType::Once,
        ]);
    }
}
