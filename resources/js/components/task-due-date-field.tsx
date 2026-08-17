import { CalendarIcon, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { formatDueDate, parseDueDate, toDueDateValue } from '@/lib/tasks';
import { cn } from '@/lib/utils';

type TaskDueDateFieldProps = {
    id: string;
    name: string;
    defaultValue?: string | null;
};

export default function TaskDueDateField({
    id,
    name,
    defaultValue,
}: TaskDueDateFieldProps) {
    const [open, setOpen] = useState(false);
    const [dueDate, setDueDate] = useState(defaultValue ?? '');
    const selected = dueDate === '' ? undefined : parseDueDate(dueDate);

    return (
        <div className="flex items-center gap-2">
            <input type="hidden" name={name} value={dueDate} />

            {/* The popover is modal so it stays clickable inside the task dialog. */}
            <Popover open={open} onOpenChange={setOpen} modal>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        className={cn(
                            'flex-1 justify-start font-normal',
                            selected === undefined && 'text-muted-foreground',
                        )}
                        data-test="due-date-trigger"
                    >
                        <CalendarIcon className="size-4" />
                        {selected === undefined
                            ? 'No due date'
                            : formatDueDate(dueDate)}
                    </Button>
                </PopoverTrigger>

                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="single"
                        selected={selected}
                        defaultMonth={selected}
                        autoFocus
                        onSelect={(date) => {
                            setDueDate(
                                date === undefined ? '' : toDueDateValue(date),
                            );
                            setOpen(false);
                        }}
                    />
                </PopoverContent>
            </Popover>

            {dueDate !== '' && (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Clear due date"
                    onClick={() => setDueDate('')}
                >
                    <X className="size-4" />
                </Button>
            )}
        </div>
    );
}
