import {
    CircleCheckBig,
    CircleDashed,
    ListTodo,
    Timer,
    TriangleAlert,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { TaskStats as TaskStatsData } from '@/types';

type StatTile = {
    label: string;
    value: number;
    icon: LucideIcon;
    accent?: string;
};

export default function TaskStats({ stats }: { stats: TaskStatsData }) {
    const tiles: StatTile[] = [
        { label: 'All tasks', value: stats.total, icon: ListTodo },
        { label: 'Pending', value: stats.pending, icon: CircleDashed },
        { label: 'In progress', value: stats.in_progress, icon: Timer },
        { label: 'Completed', value: stats.completed, icon: CircleCheckBig },
        {
            label: 'Overdue',
            value: stats.overdue,
            icon: TriangleAlert,
            accent: 'text-red-600 dark:text-red-400',
        },
    ];

    return (
        <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
            {tiles.map((tile) => (
                <div
                    key={tile.label}
                    className="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
                >
                    <div className="flex items-center justify-between gap-2">
                        <p className="text-sm text-muted-foreground">
                            {tile.label}
                        </p>
                        <tile.icon
                            className={cn(
                                'size-4 text-muted-foreground',
                                tile.value > 0 && tile.accent,
                            )}
                        />
                    </div>

                    <p
                        className={cn(
                            'mt-2 text-2xl font-semibold tabular-nums',
                            tile.value > 0 && tile.accent,
                        )}
                    >
                        {tile.value}
                    </p>
                </div>
            ))}
        </div>
    );
}
