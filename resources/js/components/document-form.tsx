import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Download, Printer } from 'lucide-react';
import type { FormEvent } from 'react';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import DateField from '@/components/date-field';
import DocumentLineItems from '@/components/document-line-items';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import {
    calculateTotals,
    documentKindLabels,
    documentStatusLabels,
    documentWording,
    formatMoney,
} from '@/lib/documents';
import { index, pdf, print } from '@/routes/documents';
import type {
    BillingDocument,
    DocumentDefaults,
    DocumentKind,
    DocumentLineDraft,
    DocumentStatus,
} from '@/types';

type DocumentFormData = {
    type: DocumentKind;
    status: DocumentStatus;
    client_name: string;
    client_email: string;
    client_address: string;
    issue_date: string;
    due_date: string;
    currency: string;
    tax_rate: string;
    discount: string;
    notes: string;
    items: DocumentLineDraft[];
};

type DocumentFormProps = {
    type: DocumentKind;
    statuses: DocumentStatus[];
    currencies: string[];
    document?: BillingDocument;
    defaults?: DocumentDefaults;
    nextNumber?: string;
};

/**
 * Show an amount held in cents the way it is typed back in.
 */
function toAmountInput(cents: number): string {
    return (cents / 100).toFixed(2);
}

function initialData(
    type: DocumentKind,
    document?: BillingDocument,
    defaults?: DocumentDefaults,
): DocumentFormData {
    if (document) {
        return {
            type: document.type,
            status: document.status,
            client_name: document.client_name,
            client_email: document.client_email ?? '',
            client_address: document.client_address ?? '',
            issue_date: document.issue_date,
            due_date: document.due_date ?? '',
            currency: document.currency,
            tax_rate: document.tax_rate,
            discount: toAmountInput(document.discount_cents),
            notes: document.notes ?? '',
            items: document.items.map((item) => ({
                description: item.description,
                quantity: item.quantity,
                unit_price: toAmountInput(item.unit_price_cents),
            })),
        };
    }

    return {
        type,
        status: 'draft',
        client_name: '',
        client_email: '',
        client_address: '',
        issue_date: defaults?.issue_date ?? '',
        due_date: defaults?.due_date ?? '',
        currency: defaults?.currency ?? 'USD',
        tax_rate: '0',
        discount: '0',
        notes: '',
        items: [{ description: '', quantity: '1', unit_price: '' }],
    };
}

export default function DocumentForm({
    type,
    statuses,
    currencies,
    document,
    defaults,
    nextNumber,
}: DocumentFormProps) {
    const isEditing = document !== undefined;
    const kind = document?.type ?? type;
    const wording = documentWording[kind];

    const form = useForm<DocumentFormData>(
        isEditing
            ? DocumentController.update(document.id)
            : DocumentController.store(),
        initialData(type, document, defaults),
    );

    const errors = form.errors as Record<string, string | undefined>;
    const totals = calculateTotals(
        form.data.items,
        form.data.tax_rate,
        form.data.discount,
    );

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        form.submit({ preserveScroll: true });
    }

    return (
        <form
            onSubmit={submit}
            className="flex h-full flex-1 flex-col gap-6 p-4"
        >
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-0.5">
                    <div className="flex items-center gap-3">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {isEditing
                                ? document.number
                                : `New ${documentKindLabels[kind].toLowerCase()}`}
                        </h1>

                        {!isEditing && nextNumber && (
                            <span className="rounded-md border border-sidebar-border/70 px-2 py-0.5 text-xs text-muted-foreground tabular-nums dark:border-sidebar-border">
                                {nextNumber}
                            </span>
                        )}
                    </div>

                    <p className="text-sm text-muted-foreground">
                        {isEditing
                            ? `${documentKindLabels[kind]} for ${document.client_name}.`
                            : `Bill for what you delivered, line by line. The number is assigned when you save.`}
                    </p>

                    {isEditing && document.converted_from && (
                        <p className="text-sm text-muted-foreground">
                            Converted from{' '}
                            <span className="tabular-nums">
                                {document.converted_from.number}
                            </span>
                            .
                        </p>
                    )}
                </div>

                <div className="flex items-center gap-2">
                    <Button variant="outline" asChild>
                        <Link href={index({ query: { type: kind } })}>
                            <ArrowLeft className="size-4" />
                            Back
                        </Link>
                    </Button>

                    {isEditing && (
                        <>
                            <Button variant="outline" asChild>
                                <Link href={print(document.id)}>
                                    <Printer className="size-4" />
                                    Print
                                </Link>
                            </Button>

                            <Button variant="outline" asChild>
                                <a href={pdf(document.id).url}>
                                    <Download className="size-4" />
                                    PDF
                                </a>
                            </Button>
                        </>
                    )}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <section className="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">Billed to</h2>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="client_name">Client</Label>

                                <Input
                                    id="client_name"
                                    value={form.data.client_name}
                                    onChange={(event) =>
                                        form.setData(
                                            'client_name',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Who is being billed?"
                                    required
                                    autoFocus
                                />

                                <InputError message={form.errors.client_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="client_email">Email</Label>

                                <Input
                                    id="client_email"
                                    type="email"
                                    value={form.data.client_email}
                                    onChange={(event) =>
                                        form.setData(
                                            'client_email',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="billing@example.com"
                                />

                                <InputError
                                    message={form.errors.client_email}
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="client_address">Address</Label>

                            <Textarea
                                id="client_address"
                                value={form.data.client_address}
                                onChange={(event) =>
                                    form.setData(
                                        'client_address',
                                        event.target.value,
                                    )
                                }
                                rows={3}
                                placeholder="Street, city, country (optional)"
                            />

                            <InputError message={form.errors.client_address} />
                        </div>
                    </section>

                    <section className="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">Lines</h2>

                        <DocumentLineItems
                            lines={form.data.items}
                            currency={form.data.currency}
                            onChange={(items) => form.setData('items', items)}
                            error={(path) => errors[path]}
                        />
                    </section>

                    <section className="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">Notes</h2>

                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                            rows={3}
                            placeholder="Payment terms, thank you note, anything the client should read."
                        />

                        <InputError message={form.errors.notes} />
                    </section>
                </div>

                <div className="space-y-6">
                    <section className="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">Details</h2>

                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>

                            <Select
                                value={form.data.status}
                                onValueChange={(status) =>
                                    form.setData(
                                        'status',
                                        status as DocumentStatus,
                                    )
                                }
                            >
                                <SelectTrigger id="status" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    {statuses.map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {documentStatusLabels[status]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={form.errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="issue_date">Issue date</Label>

                            <DateField
                                id="issue_date"
                                value={form.data.issue_date}
                                onChange={(value) =>
                                    form.setData('issue_date', value)
                                }
                                placeholder="Pick a date"
                                clearable={false}
                            />

                            <InputError message={form.errors.issue_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="due_date">{wording.dueDate}</Label>

                            <DateField
                                id="due_date"
                                value={form.data.due_date}
                                onChange={(value) =>
                                    form.setData('due_date', value)
                                }
                                placeholder="No date"
                            />

                            <InputError message={form.errors.due_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="currency">Currency</Label>

                            <Select
                                value={form.data.currency}
                                onValueChange={(currency) =>
                                    form.setData('currency', currency)
                                }
                            >
                                <SelectTrigger id="currency" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    {currencies.map((currency) => (
                                        <SelectItem
                                            key={currency}
                                            value={currency}
                                        >
                                            {currency}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={form.errors.currency} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="tax_rate">Tax rate %</Label>

                                <Input
                                    id="tax_rate"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={form.data.tax_rate}
                                    onChange={(event) =>
                                        form.setData(
                                            'tax_rate',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />

                                <InputError message={form.errors.tax_rate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="discount">Discount</Label>

                                <Input
                                    id="discount"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0"
                                    value={form.data.discount}
                                    onChange={(event) =>
                                        form.setData(
                                            'discount',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />

                                <InputError message={form.errors.discount} />
                            </div>
                        </div>
                    </section>

                    <section className="space-y-3 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">{wording.totalsHeading}</h2>

                        <dl className="space-y-2 text-sm">
                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    Subtotal
                                </dt>
                                <dd className="tabular-nums">
                                    {formatMoney(
                                        totals.subtotal,
                                        form.data.currency,
                                    )}
                                </dd>
                            </div>

                            {totals.discount > 0 && (
                                <div className="flex items-center justify-between gap-4">
                                    <dt className="text-muted-foreground">
                                        Discount
                                    </dt>
                                    <dd className="tabular-nums">
                                        −
                                        {formatMoney(
                                            totals.discount,
                                            form.data.currency,
                                        )}
                                    </dd>
                                </div>
                            )}

                            <div className="flex items-center justify-between gap-4">
                                <dt className="text-muted-foreground">
                                    Tax ({form.data.tax_rate || 0}%)
                                </dt>
                                <dd className="tabular-nums">
                                    {formatMoney(
                                        totals.tax,
                                        form.data.currency,
                                    )}
                                </dd>
                            </div>

                            <div className="flex items-center justify-between gap-4 border-t border-sidebar-border/70 pt-2 text-base font-semibold dark:border-sidebar-border">
                                <dt>Total</dt>
                                <dd className="tabular-nums">
                                    {formatMoney(
                                        totals.total,
                                        form.data.currency,
                                    )}
                                </dd>
                            </div>
                        </dl>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing}
                            data-test="save-document-button"
                        >
                            {isEditing
                                ? 'Save changes'
                                : `Create ${documentKindLabels[kind].toLowerCase()}`}
                        </Button>
                    </section>
                </div>
            </div>
        </form>
    );
}
