import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    documentKindPluralLabels,
    documentStatusLabels,
} from '@/lib/documents';
import { cn } from '@/lib/utils';
import { index } from '@/routes/documents';
import type {
    DocumentFilters as Filters,
    DocumentKind,
    DocumentStatus,
} from '@/types';

const ANY_OPTION = 'any';

type DocumentFiltersProps = {
    filters: Filters;
    type: DocumentKind;
    types: DocumentKind[];
    statuses: DocumentStatus[];
};

type FilterQuery = {
    type: DocumentKind;
    search?: string;
    status?: string;
};

function visitWithFilters(query: FilterQuery): void {
    router.get(index().url, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['documents', 'stats', 'filters', 'type', 'statuses'],
    });
}

export default function DocumentFilters({
    filters,
    type,
    types,
    statuses,
}: DocumentFiltersProps) {
    const appliedSearch = filters.search ?? '';
    const [search, setSearch] = useState(appliedSearch);
    const hasFilters = appliedSearch !== '' || filters.status !== null;

    useEffect(() => {
        if (search === appliedSearch) {
            return;
        }

        const timeout = setTimeout(() => {
            visitWithFilters({
                type,
                search: search || undefined,
                status: filters.status ?? undefined,
            });
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, appliedSearch, type, filters.status]);

    return (
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div
                className="flex rounded-lg border border-sidebar-border/70 p-1 dark:border-sidebar-border"
                role="tablist"
                aria-label="Document type"
            >
                {types.map((kind) => (
                    <button
                        key={kind}
                        type="button"
                        role="tab"
                        aria-selected={kind === type}
                        onClick={() => visitWithFilters({ type: kind })}
                        className={cn(
                            'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                            kind === type
                                ? 'bg-accent text-accent-foreground'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                        data-test={`${kind}-tab`}
                    >
                        {documentKindPluralLabels[kind]}
                    </button>
                ))}
            </div>

            <div className="relative flex-1">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search by number or client"
                    aria-label="Search documents"
                    className="pl-9"
                />
            </div>

            <Select
                value={filters.status ?? ANY_OPTION}
                onValueChange={(status) =>
                    visitWithFilters({
                        type,
                        search: appliedSearch || undefined,
                        status: status === ANY_OPTION ? undefined : status,
                    })
                }
            >
                <SelectTrigger
                    className="lg:w-44"
                    aria-label="Filter by status"
                >
                    <SelectValue />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value={ANY_OPTION}>Any status</SelectItem>

                    {statuses.map((status) => (
                        <SelectItem key={status} value={status}>
                            {documentStatusLabels[status]}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            {hasFilters && (
                <Button
                    variant="ghost"
                    onClick={() => {
                        setSearch('');
                        visitWithFilters({ type });
                    }}
                    data-test="clear-document-filters"
                >
                    <X className="size-4" />
                    Clear
                </Button>
            )}
        </div>
    );
}
