import { Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatMoney, lineTotalCents } from '@/lib/documents';
import type { DocumentLineDraft } from '@/types';

type DocumentLineItemsProps = {
    lines: DocumentLineDraft[];
    currency: string;
    onChange: (lines: DocumentLineDraft[]) => void;
    error: (path: string) => string | undefined;
};

const emptyLine: DocumentLineDraft = {
    description: '',
    quantity: '1',
    unit_price: '',
};

export default function DocumentLineItems({
    lines,
    currency,
    onChange,
    error,
}: DocumentLineItemsProps) {
    function updateLine(
        index: number,
        field: keyof DocumentLineDraft,
        value: string,
    ): void {
        onChange(
            lines.map((line, position) =>
                position === index ? { ...line, [field]: value } : line,
            ),
        );
    }

    return (
        <div className="space-y-4">
            <div className="hidden gap-3 px-1 text-xs font-medium text-muted-foreground sm:grid sm:grid-cols-[1fr_6rem_9rem_7rem_2.25rem]">
                <span>Description</span>
                <span>Qty</span>
                <span>Unit price</span>
                <span className="text-right">Line total</span>
                <span className="sr-only">Remove</span>
            </div>

            {lines.map((line, index) => (
                <div
                    key={index}
                    className="grid gap-3 rounded-lg border border-sidebar-border/70 p-3 sm:grid-cols-[1fr_6rem_9rem_7rem_2.25rem] sm:items-start sm:rounded-none sm:border-0 sm:p-0 dark:border-sidebar-border"
                    data-test={`document-line-${index}`}
                >
                    <div className="space-y-1">
                        <Label
                            htmlFor={`items-${index}-description`}
                            className="sm:sr-only"
                        >
                            Description
                        </Label>

                        <Input
                            id={`items-${index}-description`}
                            value={line.description}
                            onChange={(event) =>
                                updateLine(
                                    index,
                                    'description',
                                    event.target.value,
                                )
                            }
                            placeholder="What is being billed?"
                            required
                        />

                        <InputError
                            message={error(`items.${index}.description`)}
                        />
                    </div>

                    <div className="space-y-1">
                        <Label
                            htmlFor={`items-${index}-quantity`}
                            className="sm:sr-only"
                        >
                            Quantity
                        </Label>

                        <Input
                            id={`items-${index}-quantity`}
                            type="number"
                            inputMode="decimal"
                            step="0.01"
                            min="0.01"
                            value={line.quantity}
                            onChange={(event) =>
                                updateLine(
                                    index,
                                    'quantity',
                                    event.target.value,
                                )
                            }
                            required
                        />

                        <InputError
                            message={error(`items.${index}.quantity`)}
                        />
                    </div>

                    <div className="space-y-1">
                        <Label
                            htmlFor={`items-${index}-unit_price`}
                            className="sm:sr-only"
                        >
                            Unit price
                        </Label>

                        <Input
                            id={`items-${index}-unit_price`}
                            type="number"
                            inputMode="decimal"
                            step="0.01"
                            min="0"
                            value={line.unit_price}
                            onChange={(event) =>
                                updateLine(
                                    index,
                                    'unit_price',
                                    event.target.value,
                                )
                            }
                            placeholder="0.00"
                            required
                        />

                        <InputError
                            message={error(`items.${index}.unit_price`)}
                        />
                    </div>

                    <p className="pt-2 text-sm font-medium tabular-nums sm:text-right">
                        {formatMoney(lineTotalCents(line), currency)}
                    </p>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="text-muted-foreground hover:text-red-600 dark:hover:text-red-400"
                        aria-label={`Remove line ${index + 1}`}
                        disabled={lines.length === 1}
                        onClick={() =>
                            onChange(
                                lines.filter(
                                    (_line, position) => position !== index,
                                ),
                            )
                        }
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            ))}

            <div className="flex items-center justify-between gap-4">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => onChange([...lines, { ...emptyLine }])}
                    data-test="add-document-line"
                >
                    <Plus className="size-4" />
                    Add line
                </Button>

                <InputError message={error('items')} />
            </div>
        </div>
    );
}
