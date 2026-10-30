import { router } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/Components/Ui/Button';
import preventivePlansRoutes from '@/routes/preventive-plans';
import type { PreventiveStatusOption } from '@/Types/PreventiveMaintenance/Common';

interface Props {
    planId: number;
    transitions: PreventiveStatusOption[];
    canGenerate: boolean;
}

const ACTION_LABELS: Record<string, string> = {
    active: 'Reactivar plan',
    paused: 'Pausar plan',
    cancelled: 'Cancelar plan',
};

export function PlanActions({ planId, transitions, canGenerate }: Props) {
    const [processing, setProcessing] = useState<string | null>(null);
    const isProcessing = processing !== null;

    if (transitions.length === 0 && !canGenerate) {
        return (
            <p className="text-sm text-muted-foreground">
                Este plan está en un estado final y ya no admite cambios.
            </p>
        );
    }

    const changeStatus = (status: string) => {
        if (
            status === 'cancelled' &&
            !window.confirm(
                '¿Cancelar este plan? No se generarán más ejecuciones, pero las ya programadas se conservan.',
            )
        ) {
            return;
        }

        setProcessing(status);

        router.patch(
            preventivePlansRoutes.status.update.url(planId),
            { status },
            { preserveScroll: true, onFinish: () => setProcessing(null) },
        );
    };

    const generate = () => {
        setProcessing('generate');

        router.post(
            preventivePlansRoutes.occurrences.store.url(planId),
            {},
            { preserveScroll: true, onFinish: () => setProcessing(null) },
        );
    };

    return (
        <div className="flex flex-wrap gap-2">
            {canGenerate && (
                <Button onClick={generate} disabled={isProcessing}>
                    {processing === 'generate' && (
                        <LoaderCircle className="animate-spin" />
                    )}
                    Generar próximas ejecuciones
                </Button>
            )}

            {transitions.map((transition) => (
                <Button
                    key={transition.value}
                    variant={
                        transition.value === 'cancelled'
                            ? 'destructive'
                            : 'outline'
                    }
                    onClick={() => changeStatus(transition.value)}
                    disabled={isProcessing}
                >
                    {processing === transition.value && (
                        <LoaderCircle className="animate-spin" />
                    )}
                    {ACTION_LABELS[transition.value] ?? transition.label}
                </Button>
            ))}
        </div>
    );
}
