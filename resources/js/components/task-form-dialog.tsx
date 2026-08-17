import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import InputError from '@/components/input-error';
import TaskDueDateField from '@/components/task-due-date-field';
import { Button } from '@/components/ui/button';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { taskPriorityLabels, taskStatusLabels } from '@/lib/tasks';
import type { Task, TaskPriority, TaskStatus } from '@/types';

type TaskFormDialogProps = {
    trigger: ReactNode;
    statuses: TaskStatus[];
    priorities: TaskPriority[];
    task?: Task;
};

export default function TaskFormDialog({
    trigger,
    statuses,
    priorities,
    task,
}: TaskFormDialogProps) {
    const [open, setOpen] = useState(false);
    const isEditing = task !== undefined;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit task' : 'New task'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Change the details of this task.'
                            : 'Add a task to your list and keep track of what is next.'}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...(isEditing
                        ? TaskController.update.form(task.id)
                        : TaskController.store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess={!isEditing}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>

                                <Input
                                    id="title"
                                    name="title"
                                    defaultValue={task?.title}
                                    required
                                    autoFocus
                                    placeholder="What needs to be done?"
                                />

                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>

                                <Textarea
                                    id="description"
                                    name="description"
                                    defaultValue={task?.description ?? ''}
                                    rows={3}
                                    placeholder="Add any extra details (optional)"
                                />

                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>

                                    <Select
                                        name="status"
                                        defaultValue={task?.status ?? 'pending'}
                                    >
                                        <SelectTrigger
                                            id="status"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {statuses.map((status) => (
                                                <SelectItem
                                                    key={status}
                                                    value={status}
                                                >
                                                    {taskStatusLabels[status]}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    <InputError message={errors.status} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="priority">Priority</Label>

                                    <Select
                                        name="priority"
                                        defaultValue={
                                            task?.priority ?? 'medium'
                                        }
                                    >
                                        <SelectTrigger
                                            id="priority"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {priorities.map((priority) => (
                                                <SelectItem
                                                    key={priority}
                                                    value={priority}
                                                >
                                                    {
                                                        taskPriorityLabels[
                                                            priority
                                                        ]
                                                    }
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    <InputError message={errors.priority} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="due_date">Due date</Label>

                                <TaskDueDateField
                                    id="due_date"
                                    name="due_date"
                                    defaultValue={task?.due_date}
                                />

                                <InputError message={errors.due_date} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    data-test="save-task-button"
                                >
                                    {isEditing ? 'Save changes' : 'Create task'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
