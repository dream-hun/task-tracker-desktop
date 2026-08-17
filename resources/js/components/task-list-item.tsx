import { Form, router } from '@inertiajs/react';
import { CalendarDays, CircleCheckBig, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import TaskStatusController from '@/actions/App/Http/Controllers/TaskStatusController';
import TaskFormDialog from '@/components/task-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    formatCompletedAt,
    formatDueDate,
    taskPriorityClasses,
    taskPriorityLabels,
    taskStatusClasses,
    taskStatusLabels,
} from '@/lib/tasks';
import { cn } from '@/lib/utils';
import type { Task, TaskPriority, TaskStatus } from '@/types';

type TaskListItemProps = {
    task: Task;
    statuses: TaskStatus[];
    priorities: TaskPriority[];
};

export default function TaskListItem({
    task,
    statuses,
    priorities,
}: TaskListItemProps) {
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const isCompleted = task.status === 'completed';

    function toggleCompletion(completed: boolean): void {
        router.patch(
            TaskStatusController(task.id).url,
            { status: completed ? 'completed' : 'pending' },
            { preserveScroll: true, preserveState: true },
        );
    }

    return (
        <div
            className="flex items-start gap-3 rounded-xl border border-sidebar-border/70 bg-card p-4 transition-colors hover:border-sidebar-border dark:border-sidebar-border"
            data-test={`task-${task.id}`}
        >
            <Checkbox
                checked={isCompleted}
                onCheckedChange={(checked) =>
                    toggleCompletion(checked === true)
                }
                aria-label={
                    isCompleted
                        ? `Reopen ${task.title}`
                        : `Complete ${task.title}`
                }
                className="mt-1"
            />

            <div className="min-w-0 flex-1 space-y-1.5">
                <div className="flex flex-wrap items-center gap-2">
                    <span
                        className={cn(
                            'font-medium',
                            isCompleted && 'text-muted-foreground line-through',
                        )}
                    >
                        {task.title}
                    </span>

                    <Badge
                        variant="outline"
                        className={taskStatusClasses[task.status]}
                    >
                        {taskStatusLabels[task.status]}
                    </Badge>

                    <Badge
                        variant="outline"
                        className={taskPriorityClasses[task.priority]}
                    >
                        {taskPriorityLabels[task.priority]}
                    </Badge>
                </div>

                {task.description && (
                    <p className="text-sm whitespace-pre-line text-muted-foreground">
                        {task.description}
                    </p>
                )}

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                    {task.due_date && (
                        <p
                            className={cn(
                                'flex items-center gap-1.5',
                                task.is_overdue
                                    ? 'font-medium text-red-600 dark:text-red-400'
                                    : 'text-muted-foreground',
                            )}
                        >
                            <CalendarDays className="size-3.5" />
                            {task.is_overdue ? 'Overdue — due' : 'Due'}{' '}
                            {formatDueDate(task.due_date)}
                        </p>
                    )}

                    {isCompleted && task.completed_at && (
                        <p className="flex items-center gap-1.5 text-muted-foreground">
                            <CircleCheckBig className="size-3.5" />
                            Completed {formatCompletedAt(task.completed_at)}
                        </p>
                    )}
                </div>
            </div>

            <div className="flex items-center gap-1">
                <TaskFormDialog
                    task={task}
                    statuses={statuses}
                    priorities={priorities}
                    trigger={
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={`Edit ${task.title}`}
                        >
                            <Pencil className="size-4" />
                        </Button>
                    }
                />

                <Dialog
                    open={confirmingDeletion}
                    onOpenChange={setConfirmingDeletion}
                >
                    <DialogTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="text-muted-foreground hover:text-red-600 dark:hover:text-red-400"
                            aria-label={`Delete ${task.title}`}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </DialogTrigger>

                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete this task?</DialogTitle>
                            <DialogDescription>
                                “{task.title}” will be permanently removed. This
                                cannot be undone.
                            </DialogDescription>
                        </DialogHeader>

                        <Form
                            {...TaskController.destroy.form(task.id)}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setConfirmingDeletion(false)}
                        >
                            {({ processing }) => (
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>

                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                        data-test="confirm-delete-task-button"
                                    >
                                        Delete task
                                    </Button>
                                </DialogFooter>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
