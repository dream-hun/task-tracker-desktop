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
import { taskPriorityLabels, taskStatusLabels } from '@/lib/tasks';
import { index } from '@/routes/tasks';
import type { TaskFilters as Filters, TaskPriority, TaskStatus } from '@/types';

const ANY_OPTION = 'any';

type TaskFiltersProps = {
    filters: Filters;
    statuses: TaskStatus[];
    priorities: TaskPriority[];
};

type FilterQuery = {
    search?: string;
    status?: string;
    priority?: string;
};

function visitWithFilters(query: FilterQuery): void {
    router.get(index().url, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['tasks', 'stats', 'filters'],
    });
}

export default function TaskFilters({
    filters,
    statuses,
    priorities,
}: TaskFiltersProps) {
    const appliedSearch = filters.search ?? '';
    const [search, setSearch] = useState(appliedSearch);
    const hasFilters =
        appliedSearch !== '' ||
        filters.status !== null ||
        filters.priority !== null;

    useEffect(() => {
        if (search === appliedSearch) {
            return;
        }

        const timeout = setTimeout(() => {
            visitWithFilters({
                search: search || undefined,
                status: filters.status ?? undefined,
                priority: filters.priority ?? undefined,
            });
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, appliedSearch, filters.status, filters.priority]);

    function clearFilters(): void {
        setSearch('');
        visitWithFilters({});
    }

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div className="relative flex-1">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search tasks"
                    aria-label="Search tasks"
                    className="pl-9"
                />
            </div>

            <Select
                value={filters.status ?? ANY_OPTION}
                onValueChange={(status) =>
                    visitWithFilters({
                        search: appliedSearch || undefined,
                        status: status === ANY_OPTION ? undefined : status,
                        priority: filters.priority ?? undefined,
                    })
                }
            >
                <SelectTrigger
                    className="sm:w-40"
                    aria-label="Filter by status"
                >
                    <SelectValue />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value={ANY_OPTION}>Any status</SelectItem>

                    {statuses.map((status) => (
                        <SelectItem key={status} value={status}>
                            {taskStatusLabels[status]}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Select
                value={filters.priority ?? ANY_OPTION}
                onValueChange={(priority) =>
                    visitWithFilters({
                        search: appliedSearch || undefined,
                        status: filters.status ?? undefined,
                        priority:
                            priority === ANY_OPTION ? undefined : priority,
                    })
                }
            >
                <SelectTrigger
                    className="sm:w-40"
                    aria-label="Filter by priority"
                >
                    <SelectValue />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value={ANY_OPTION}>Any priority</SelectItem>

                    {priorities.map((priority) => (
                        <SelectItem key={priority} value={priority}>
                            {taskPriorityLabels[priority]}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            {hasFilters && (
                <Button
                    variant="ghost"
                    onClick={clearFilters}
                    data-test="clear-task-filters"
                >
                    <X className="size-4" />
                    Clear
                </Button>
            )}
        </div>
    );
}
