<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tickets = DB::table('tickets')
            ->whereNotNull('assigned_to')
            ->get(['id', 'assigned_to', 'status', 'updated_at']);

        foreach ($tickets as $ticket) {
            if (DB::table('ticket_assignments')->where('ticket_id', $ticket->id)->exists()) {
                continue;
            }

            $transition = DB::table('ticket_status_transitions')
                ->where('ticket_id', $ticket->id)
                ->where('to_status', 'assigned')
                ->orderByDesc('id')
                ->first();

            $isFinal = in_array($ticket->status, ['closed', 'cancelled'], true);

            DB::table('ticket_assignments')->insert([
                'ticket_id' => $ticket->id,
                'assigned_to' => $ticket->assigned_to,
                'assigned_by' => $transition->changed_by ?? null,
                'assigned_at' => $transition->created_at ?? $ticket->updated_at,
                'ended_at' => $isFinal ? $ticket->updated_at : null,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ticket_assignments')->delete();
    }
};
