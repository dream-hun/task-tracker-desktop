import { CalendarIcon, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { formatDate, parseDateValue, toDateValue } from '@/lib/dates';
import { cn } from '@/lib/utils';

type DateFieldProps = {
    id: string;
    /** Submits the picked `Y-m-d` value with a plain form. */
    name?: string;
    /** Controls the field. Leave out to let the field track its own value. */
    value?: string | null;
    defaultValue?: string | null;
    onChange?: (value: string) => void;
    placeholder?: string;
    clearable?: boolean;
    dataTest?: string;
};

export default function DateField({
    id,
    name,
    value,
    defaultValue,
    onChange,
    placeholder = 'No date',
    clearable = true,
    dataTest,
}: DateFieldProps) {
    const [open, setOpen] = useState(false);
    const [ownValue, setOwnValue] = useState(defaultValue ?? '');
    const date = (value === undefined ? ownValue : (value ?? '')) || '';
    const selected = date === '' ? undefined : parseDateValue(date);

    function pick(next: string): void {
        if (value === undefined) {
            setOwnValue(next);
        }

        onChange?.(next);
    }

    return (
        <div className="flex items-center gap-2">
            {name && <input type="hidden" name={name} value={date} />}

            {/* The popover is modal so it stays clickable inside a dialog. */}
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
                        data-test={dataTest}
                    >
                        <CalendarIcon className="size-4" />
                        {selected === undefined
                            ? placeholder
                            : formatDate(date)}
                    </Button>
                </PopoverTrigger>

                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="single"
                        selected={selected}
                        defaultMonth={selected}
                        autoFocus
                        onSelect={(picked) => {
                            pick(
                                picked === undefined ? '' : toDateValue(picked),
                            );
                            setOpen(false);
                        }}
                    />
                </PopoverContent>
            </Popover>

            {clearable && date !== '' && (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Clear date"
                    onClick={() => pick('')}
                >
                    <X className="size-4" />
                </Button>
            )}
        </div>
    );
}
