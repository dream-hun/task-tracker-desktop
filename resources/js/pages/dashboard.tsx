import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, PartyPopper } from 'lucide-react';
import TaskStats from '@/components/task-stats';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatDueDate,
    taskPriorityClasses,
    taskPriorityLabels,
    taskStatusClasses,
    taskStatusLabels,
} from '@/lib/tasks';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as tasks } from '@/routes/tasks';
import type { Task, TaskStats as Stats } from '@/types';

type DashboardProps = {
    stats: Stats;
    upcomingTasks: Task[];
};

export default function Dashboard({ stats, upcomingTasks }: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="space-y-0.5">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        A quick look at where your tasks stand.
                    </p>
                </div>

                <TaskStats stats={stats} />

                <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <div className="flex items-center justify-between gap-4 border-b border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="space-y-0.5">
                            <h2 className="font-medium">Up next</h2>
                            <p className="text-sm text-muted-foreground">
                                Your most urgent unfinished tasks.
                            </p>
                        </div>

                        <Button variant="outline" size="sm" asChild>
                            <Link href={tasks()} prefetch>
                                All tasks
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>

                    {upcomingTasks.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 p-12 text-center">
                            <PartyPopper className="size-8 text-muted-foreground" />
                            <p className="font-medium">Nothing pending</p>
                            <p className="text-sm text-muted-foreground">
                                Every task is done. Add a new one whenever you
                                are ready.
                            </p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            {upcomingTasks.map((task) => (
                                <li
                                    key={task.id}
                                    className="flex flex-wrap items-center gap-x-3 gap-y-1.5 p-4"
                                >
                                    <span className="font-medium">
                                        {task.title}
                                    </span>

                                    <Badge
                                        variant="outline"
                                        className={
                                            taskStatusClasses[task.status]
                                        }
                                    >
                                        {taskStatusLabels[task.status]}
                                    </Badge>

                                    <Badge
                                        variant="outline"
                                        className={
                                            taskPriorityClasses[task.priority]
                                        }
                                    >
                                        {taskPriorityLabels[task.priority]}
                                    </Badge>

                                    {task.due_date && (
                                        <span
                                            className={cn(
                                                'ml-auto flex items-center gap-1.5 text-xs',
                                                task.is_overdue
                                                    ? 'font-medium text-red-600 dark:text-red-400'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            <CalendarDays className="size-3.5" />
                                            {task.is_overdue
                                                ? 'Overdue — due'
                                                : 'Due'}{' '}
                                            {formatDueDate(task.due_date)}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
