import { Head } from '@inertiajs/react';

import type { StatisticsPageProps } from '@/Types/Statistics/Statistics';

import StatisticsIndex from './Statistics/Index';

type Props = Partial<StatisticsPageProps>;

export default function Dashboard(props: Props) {
    if (!props.summary) {
        return (
            <>
                <Head title="Dashboard" />

                <div className="flex flex-1 flex-col gap-6 p-6">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h1>

                    <p className="text-sm text-muted-foreground">
                        Usa el menú lateral para gestionar los casos y el
                        mantenimiento preventivo.
                    </p>
                </div>
            </>
        );
    }

    return <StatisticsIndex {...(props as StatisticsPageProps)} />;
}
