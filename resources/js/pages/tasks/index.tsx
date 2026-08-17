import { Head, Link } from '@inertiajs/react';
import { ListTodo, Plus } from 'lucide-react';
import TaskFilters from '@/components/task-filters';
import TaskFormDialog from '@/components/task-form-dialog';
import TaskListItem from '@/components/task-list-item';
import TaskStats from '@/components/task-stats';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/tasks';
import type {
    Paginated,
    Task,
    TaskFilters as Filters,
    TaskPriority,
    TaskStats as Stats,
    TaskStatus,
} from '@/types';

type TasksIndexProps = {
    tasks: Paginated<Task>;
    stats: Stats;
    filters: Filters;
    statuses: TaskStatus[];
    priorities: TaskPriority[];
};

export default function TasksIndex({
    tasks,
    stats,
    filters,
    statuses,
    priorities,
}: TasksIndexProps) {
    const hasFilters =
        filters.search !== null ||
        filters.status !== null ||
        filters.priority !== null;

    return (
        <>
            <Head title="Tasks" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-0.5">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Tasks
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Keep track of what you need to do and how far along
                            it is.
                        </p>
                    </div>

                    <TaskFormDialog
                        statuses={statuses}
                        priorities={priorities}
                        trigger={
                            <Button data-test="new-task-button">
                                <Plus className="size-4" />
                                New task
                            </Button>
                        }
                    />
                </div>

                <TaskStats stats={stats} />

                <TaskFilters
                    filters={filters}
                    statuses={statuses}
                    priorities={priorities}
                />

                {tasks.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border">
                        <ListTodo className="size-8 text-muted-foreground" />
                        <p className="font-medium">
                            {hasFilters
                                ? 'No tasks match these filters'
                                : 'No tasks yet'}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {hasFilters
                                ? 'Try a different search or clear the filters.'
                                : 'Create your first task to get started.'}
                        </p>
                    </div>
                ) : (
                    <div className="flex flex-col gap-3">
                        {tasks.data.map((task) => (
                            <TaskListItem
                                key={task.id}
                                task={task}
                                statuses={statuses}
                                priorities={priorities}
                            />
                        ))}
                    </div>
                )}

                {tasks.last_page > 1 && (
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-sm text-muted-foreground">
                            Showing {tasks.from}–{tasks.to} of {tasks.total}{' '}
                            tasks
                        </p>

                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={tasks.prev_page_url === null}
                                asChild={tasks.prev_page_url !== null}
                            >
                                {tasks.prev_page_url === null ? (
                                    'Previous'
                                ) : (
                                    <Link
                                        href={tasks.prev_page_url}
                                        preserveScroll
                                    >
                                        Previous
                                    </Link>
                                )}
                            </Button>

                            <Button
                                variant="outline"
                                size="sm"
                                disabled={tasks.next_page_url === null}
                                asChild={tasks.next_page_url !== null}
                            >
                                {tasks.next_page_url === null ? (
                                    'Next'
                                ) : (
                                    <Link
                                        href={tasks.next_page_url}
                                        preserveScroll
                                    >
                                        Next
                                    </Link>
                                )}
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

TasksIndex.layout = {
    breadcrumbs: [
        {
            title: 'Tasks',
            href: index(),
        },
    ],
};
