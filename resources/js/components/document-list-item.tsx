import { Form, Link, router } from '@inertiajs/react';
import {
    CalendarDays,
    Download,
    FileOutput,
    Pencil,
    Printer,
    Trash2,
    User,
} from 'lucide-react';
import { useState } from 'react';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import DocumentConversionController from '@/actions/App/Http/Controllers/DocumentConversionController';
import DocumentStatusController from '@/actions/App/Http/Controllers/DocumentStatusController';
import DocumentStatusBadge from '@/components/document-status-badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatDate } from '@/lib/dates';
import {
    documentStatusLabels,
    documentWording,
    formatMoney,
} from '@/lib/documents';
import { cn } from '@/lib/utils';
import { edit, pdf, print } from '@/routes/documents';
import type { BillingDocument, DocumentStatus } from '@/types';

type DocumentListItemProps = {
    document: BillingDocument;
    statuses: DocumentStatus[];
};

export default function DocumentListItem({
    document,
    statuses,
}: DocumentListItemProps) {
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const wording = documentWording[document.type];

    function changeStatus(status: string): void {
        router.patch(
            DocumentStatusController(document.id).url,
            { status },
            { preserveScroll: true, preserveState: true },
        );
    }

    return (
        <div
            className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4 transition-colors hover:border-sidebar-border lg:flex-row lg:items-center dark:border-sidebar-border"
            data-test={`document-${document.id}`}
        >
            <div className="min-w-0 flex-1 space-y-1.5">
                <div className="flex flex-wrap items-center gap-2">
                    <Link
                        href={edit(document.id)}
                        className="font-medium tabular-nums underline-offset-4 hover:underline"
                    >
                        {document.number}
                    </Link>

                    <DocumentStatusBadge
                        status={document.status}
                        isOverdue={document.is_overdue}
                    />
                </div>

                <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                    <User className="size-3.5 shrink-0" />
                    <span className="truncate">{document.client_name}</span>
                </p>

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <span className="flex items-center gap-1.5">
                        <CalendarDays className="size-3.5" />
                        Issued {formatDate(document.issue_date)}
                    </span>

                    {document.due_date && (
                        <span
                            className={cn(
                                document.is_overdue &&
                                    'font-medium text-red-600 dark:text-red-400',
                            )}
                        >
                            {wording.dueDate} {formatDate(document.due_date)}
                        </span>
                    )}

                    <span>
                        {document.items.length}{' '}
                        {document.items.length === 1 ? 'line' : 'lines'}
                    </span>
                </div>
            </div>

            <div className="flex items-center justify-between gap-3 lg:justify-end">
                <p className="text-lg font-semibold tabular-nums">
                    {formatMoney(document.total_cents, document.currency)}
                </p>

                <Select value={document.status} onValueChange={changeStatus}>
                    <SelectTrigger
                        className="w-36"
                        aria-label={`Status of ${document.number}`}
                    >
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

                <div className="flex items-center gap-1">
                    {document.type === 'quotation' && (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Convert ${document.number} to an invoice`}
                                    onClick={() =>
                                        router.post(
                                            DocumentConversionController(
                                                document.id,
                                            ).url,
                                        )
                                    }
                                    data-test={`convert-document-${document.id}`}
                                >
                                    <FileOutput className="size-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Convert to invoice</TooltipContent>
                        </Tooltip>
                    )}

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={`Download ${document.number} as PDF`}
                                asChild
                            >
                                {/* A plain anchor, so the browser saves the file
                                    instead of Inertia swallowing the response. */}
                                <a href={pdf(document.id).url}>
                                    <Download className="size-4" />
                                </a>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Download PDF</TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={`Print ${document.number}`}
                                asChild
                            >
                                <Link href={print(document.id)}>
                                    <Printer className="size-4" />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Print</TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={`Edit ${document.number}`}
                                asChild
                            >
                                <Link href={edit(document.id)}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Edit</TooltipContent>
                    </Tooltip>

                    <Dialog
                        open={confirmingDeletion}
                        onOpenChange={setConfirmingDeletion}
                    >
                        <DialogTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="text-muted-foreground hover:text-red-600 dark:hover:text-red-400"
                                aria-label={`Delete ${document.number}`}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    Delete {document.number}?
                                </DialogTitle>
                                <DialogDescription>
                                    The document and everything it bills will be
                                    permanently removed. This cannot be undone.
                                </DialogDescription>
                            </DialogHeader>

                            <Form
                                {...DocumentController.destroy.form(
                                    document.id,
                                )}
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
                                            data-test="confirm-delete-document-button"
                                        >
                                            Delete
                                        </Button>
                                    </DialogFooter>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>
    );
}
