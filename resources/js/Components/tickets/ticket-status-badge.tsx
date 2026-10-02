import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const COLOR_CLASSES: Record<string, string> = {
    gray: 'bg-muted text-muted-foreground',
    info: 'bg-sky-500/10 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300',
    warning:
        'bg-amber-500/15 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
    success:
        'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
    danger: 'bg-destructive/10 text-destructive dark:bg-destructive/20',
};

interface Props {
    label: string;
    color: string;
    className?: string;
}

export function TicketStatusBadge({ label, color, className }: Props) {
    return (
        <Badge
            className={cn(
                'border-transparent',
                COLOR_CLASSES[color] ?? COLOR_CLASSES.gray,
                className,
            )}
        >
            {label}
        </Badge>
    );
}
