import {
    Area,
    AreaChart,
    CartesianGrid,
    XAxis,
    YAxis,
} from 'recharts';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/Ui/Card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/Components/Ui/Chart';
import type { ChartConfig } from '@/Components/Ui/Chart';
import type { StatisticsTrendPoint } from '@/Types/Statistics/Statistics';

interface Props {
    data: StatisticsTrendPoint[];
}

const chartConfig = {
    reported: {
        label: 'Reportados',
        color: 'var(--chart-1)',
    },
    resolved: {
        label: 'Resueltos',
        color: 'var(--chart-2)',
    },
    preventive_completed: {
        label: 'Preventivos completados',
        color: 'var(--chart-3)',
    },
} satisfies ChartConfig;

export function TrendCard({ data }: Props) {
    return (
        <Card className="xl:col-span-2">
            <CardHeader>
                <CardTitle>Tendencia operativa</CardTitle>

                <CardDescription>
                    Casos reportados, casos resueltos y preventivos
                    completados en el periodo seleccionado.
                </CardDescription>
            </CardHeader>

            <CardContent>
                {data.length === 0 ? (
                    <p className="py-16 text-center text-sm text-muted-foreground">
                        No hay información para el periodo seleccionado.
                    </p>
                ) : (
                    <ChartContainer
                        config={chartConfig}
                        className="aspect-auto h-[300px] w-full"
                    >
                        <AreaChart
                            data={data}
                            margin={{ top: 8, right: 12, left: 0, bottom: 0 }}
                        >
                            <CartesianGrid vertical={false} />

                            <XAxis
                                dataKey="label"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                minTickGap={24}
                            />

                            <YAxis
                                allowDecimals={false}
                                tickLine={false}
                                axisLine={false}
                                width={32}
                            />

                            <ChartTooltip
                                content={<ChartTooltipContent />}
                            />

                            <Area
                                type="monotone"
                                dataKey="reported"
                                stroke="var(--color-reported)"
                                fill="var(--color-reported)"
                                fillOpacity={0.12}
                                strokeWidth={2}
                            />

                            <Area
                                type="monotone"
                                dataKey="resolved"
                                stroke="var(--color-resolved)"
                                fill="var(--color-resolved)"
                                fillOpacity={0.12}
                                strokeWidth={2}
                            />

                            <Area
                                type="monotone"
                                dataKey="preventive_completed"
                                stroke="var(--color-preventive_completed)"
                                fill="var(--color-preventive_completed)"
                                fillOpacity={0.12}
                                strokeWidth={2}
                            />
                        </AreaChart>
                    </ChartContainer>
                )}

                <div className="mt-4 flex flex-wrap gap-4 text-xs text-muted-foreground">
                    {Object.entries(chartConfig).map(([key, config]) => (
                        <span key={key} className="inline-flex items-center gap-2">
                            <span
                                className="size-2.5 rounded-full"
                                style={{ backgroundColor: config.color }}
                            />

                            {config.label}
                        </span>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
