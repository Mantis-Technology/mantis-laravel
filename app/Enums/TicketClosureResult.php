<?php

namespace App\Enums;

/**
 * Outcome documented when a maintenance case is closed (REQ-18). A case with
 * pending work is not closed: it stays resolved until nothing else is
 * required, so there is no "partial" outcome here.
 */
enum TicketClosureResult: string
{
    case Solved = 'solved';
    case SolvedWithRecommendations = 'solved_with_recommendations';

    public function label(): string
    {
        return match ($this) {
            self::Solved => 'Solucionado',
            self::SolvedWithRecommendations => 'Solucionado con recomendaciones',
        };
    }

    /**
     * Whether the closure must include observations for future
     * interventions.
     */
    public function requiresObservations(): bool
    {
        return $this === self::SolvedWithRecommendations;
    }
}
