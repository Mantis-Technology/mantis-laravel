import {
    Bar,
    BarChart,
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
import type { StatisticsCategoryRow } from '@/Types/Statistics/Statistics';

interface Props {
    data: StatisticsCategoryRow[];
}

const chartConfig = {
    tickets: {
        label: 'Casos',
        color: 'var(--chart-1)',
    },
    preventive: {
        label: 'Preventivos',
        color: 'var(--chart-4)',
    },
} satisfies ChartConfig;

export function CategoryCard({ data }: Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Distribución por categoría</CardTitle>

                <CardDescription>
                    Casos y mantenimientos preventivos agrupados por categoría.
                </CardDescription>
            </CardHeader>

            <CardContent>
                {data.length === 0 ? (
                    <p className="py-16 text-center text-sm text-muted-foreground">
                        No hay información para el periodo seleccionado.
                    </p>
                ) : (
                    <>
                        <ChartContainer
                            config={chartConfig}
                            className="aspect-auto w-full"
                            style={{
                                height: Math.max(240, data.length * 42),
                            }}
                        >
                            <BarChart
                                data={data}
                                layout="vertical"
                                margin={{ top: 4, right: 12, left: 8, bottom: 4 }}
                            >
                                <CartesianGrid horizontal={false} />

                                <XAxis
                                    type="number"
                                    allowDecimals={false}
                                    tickLine={false}
                                    axisLine={false}
                                />

                                <YAxis
                                    type="category"
                                    dataKey="name"
                                    width={130}
                                    tickLine={false}
                                    axisLine={false}
                                />

                                <ChartTooltip
                                    content={<ChartTooltipContent />}
                                />

                                <Bar
                                    dataKey="tickets"
                                    fill="var(--color-tickets)"
                                    radius={[0, 4, 4, 0]}
                                />

                                <Bar
                                    dataKey="preventive"
                                    fill="var(--color-preventive)"
                                    radius={[0, 4, 4, 0]}
                                />
                            </BarChart>
                        </ChartContainer>

                        <div className="mt-4 flex flex-wrap gap-4 text-xs text-muted-foreground">
                            {Object.entries(chartConfig).map(([key, config]) => (
                                <span
                                    key={key}
                                    className="inline-flex items-center gap-2"
                                >
                                    <span
                                        className="size-2.5 rounded-full"
                                        style={{
                                            backgroundColor: config.color,
                                        }}
                                    />

                                    {config.label}
                                </span>
                            ))}
                        </div>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
