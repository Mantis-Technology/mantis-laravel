<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Does not provide a default `asset_card_id`: unlike `User`,
     * `AssetCard` does not have its own factory yet (see
     * AssetCardCodeTest), so each test must pass one explicitly, created
     * with `AssetCard::query()->create(...)`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reported_by' => User::factory(),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::Reported,
        ];
    }

    public function categorized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Categorized,
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Assigned,
            'assigned_to' => User::factory(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::InProgress,
            'assigned_to' => User::factory(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Resolved,
            'assigned_to' => User::factory(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Closed,
            'assigned_to' => User::factory(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Cancelled,
        ]);
    }
}
