import {
    CircleCheckBig,
    FileClock,
    Files,
    Hourglass,
    TriangleAlert,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import {
    documentKindPluralLabels,
    documentWording,
    formatMoney,
} from '@/lib/documents';
import { cn } from '@/lib/utils';
import type { DocumentKind, DocumentStats as Stats } from '@/types';

type StatTile = {
    label: string;
    value: number;
    amount?: number;
    icon: LucideIcon;
    accent?: string;
};

type DocumentStatsProps = {
    stats: Stats;
    type: DocumentKind;
};

export default function DocumentStats({ stats, type }: DocumentStatsProps) {
    const wording = documentWording[type];

    const tiles: StatTile[] = [
        {
            label: `All ${documentKindPluralLabels[type].toLowerCase()}`,
            value: stats.total,
            icon: Files,
        },
        { label: 'Drafts', value: stats.drafts, icon: FileClock },
        {
            label: wording.open,
            value: stats.open,
            amount: stats.open_cents,
            icon: Hourglass,
        },
        {
            label: 'Overdue',
            value: stats.overdue,
            icon: TriangleAlert,
            accent: 'text-red-600 dark:text-red-400',
        },
        {
            label: wording.settled,
            value: stats.settled,
            amount: stats.settled_cents,
            icon: CircleCheckBig,
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
                                'size-4 shrink-0 text-muted-foreground',
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

                    {tile.amount !== undefined && stats.currency !== null && (
                        <p className="mt-0.5 text-xs text-muted-foreground tabular-nums">
                            {formatMoney(tile.amount, stats.currency)}
                        </p>
                    )}
                </div>
            ))}
        </div>
    );
}
