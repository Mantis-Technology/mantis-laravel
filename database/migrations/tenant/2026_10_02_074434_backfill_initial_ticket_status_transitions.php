<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Gives every existing ticket an initial entry in the lifecycle audit,
     * so cases reported before REQ-14 keep a coherent history.
     */
    public function up(): void
    {
        $tickets = DB::table('tickets')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('ticket_status_transitions')
                    ->whereColumn('ticket_status_transitions.ticket_id', 'tickets.id');
            })
            ->get(['id', 'status', 'reported_by', 'created_at']);

        $rows = $tickets
            ->map(fn (stdClass $ticket): array => [
                'ticket_id' => $ticket->id,
                'from_status' => null,
                'to_status' => $ticket->status,
                'changed_by' => $ticket->reported_by,
                'note' => null,
                'created_at' => $ticket->created_at,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('ticket_status_transitions')->insert($rows);
        }
    }

    /**
     * Irreversible on purpose: the backfilled rows are valid audit entries
     * and cannot be told apart from the ones created by the application.
     */
    public function down(): void
    {
        //
    }
};
