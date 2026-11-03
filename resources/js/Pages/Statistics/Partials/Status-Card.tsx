import { Cell, Pie, PieChart } from 'recharts';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/Ui/Card';
import { ChartContainer } from '@/Components/Ui/Chart';
import type { ChartConfig } from '@/Components/Ui/Chart';
import type { StatisticsDistributionRow } from '@/Types/Statistics/Statistics';

interface Props {
    data: StatisticsDistributionRow[];
}

const chartConfig = {} satisfies ChartConfig;

const COLOR_MAP: Record<string, string> = {
    gray: 'var(--muted-foreground)',
    info: '#0284c7',
    warning: '#d97706',
    success: '#059669',
    danger: 'var(--destructive)',
};

export function StatusCard({ data }: Props) {
    const total = data.reduce((sum, row) => sum + row.count, 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Distribución por estado</CardTitle>

                <CardDescription>
                    Casos de mantenimiento según su estado actual.
                </CardDescription>
            </CardHeader>

            <CardContent>
                {total === 0 ? (
                    <p className="py-16 text-center text-sm text-muted-foreground">
                        No hay casos para el periodo seleccionado.
                    </p>
                ) : (
                    <>
                        <ChartContainer
                            config={chartConfig}
                            className="mx-auto aspect-square h-[240px] w-full"
                        >
                            <PieChart>
                                <Pie
                                    data={data}
                                    dataKey="count"
                                    nameKey="label"
                                    innerRadius={55}
                                    outerRadius={90}
                                    paddingAngle={2}
                                >
                                    {data.map((row) => (
                                        <Cell
                                            key={row.value}
                                            fill={
                                                COLOR_MAP[row.color] ??
                                                COLOR_MAP.gray
                                            }
                                        />
                                    ))}
                                </Pie>
                            </PieChart>
                        </ChartContainer>

                        <ul className="mt-4 grid grid-cols-2 gap-2 text-xs">
                            {data.map((row) => (
                                <li
                                    key={row.value}
                                    className="flex items-center justify-between gap-2 rounded-md bg-muted/50 px-2 py-1.5"
                                >
                                    <span className="inline-flex items-center gap-2 text-muted-foreground">
                                        <span
                                            className="size-2.5 rounded-full"
                                            style={{
                                                backgroundColor:
                                                    COLOR_MAP[row.color] ??
                                                    COLOR_MAP.gray,
                                            }}
                                        />

                                        {row.label}
                                    </span>

                                    <span className="font-medium tabular-nums">
                                        {row.count}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
