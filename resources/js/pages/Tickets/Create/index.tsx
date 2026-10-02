import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { TicketForm } from './partials/form/ticket-form';

interface AssetCardOption {
    id: number;
    code: string;
}

interface LocationOption {
    id: number;
    name: string;
    children?: LocationOption[];
}

interface Props {
    assetCard: AssetCardOption | null;
    assetCards: AssetCardOption[];
    locations: LocationOption[];
    action: string;
}

export default function TicketsCreate({
    assetCard,
    assetCards,
    locations,
    action,
}: Props) {
    return (
        <div className="w-full py-10">
            <Link
                href="/dashboard"
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver al dashboard
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Reportar incidencia
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    {assetCard
                        ? `Registra una falla en el activo ${assetCard.code}.`
                        : 'Registra una falla o incidencia en un activo de la empresa.'}
                </p>
            </div>

            <TicketForm
                action={action}
                assetCard={assetCard}
                assetCards={assetCards}
                locations={locations}
            />
        </div>
    );
}
