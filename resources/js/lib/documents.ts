import type { DocumentKind, DocumentLineDraft, DocumentStatus } from '@/types';

export const documentKindLabels: Record<DocumentKind, string> = {
    invoice: 'Invoice',
    quotation: 'Quotation',
};

export const documentKindPluralLabels: Record<DocumentKind, string> = {
    invoice: 'Invoices',
    quotation: 'Quotations',
};

export const documentStatusLabels: Record<DocumentStatus, string> = {
    draft: 'Draft',
    sent: 'Sent',
    accepted: 'Accepted',
    declined: 'Declined',
    paid: 'Paid',
    cancelled: 'Cancelled',
};

export const documentStatusClasses: Record<DocumentStatus, string> = {
    draft: 'border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200',
    sent: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
    accepted:
        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
    paid: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
    declined:
        'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
    cancelled:
        'border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-400',
};

/**
 * Wording that differs between what is billed and what is only offered.
 */
export const documentWording: Record<
    DocumentKind,
    { dueDate: string; open: string; settled: string; totalsHeading: string }
> = {
    invoice: {
        dueDate: 'Due date',
        open: 'Awaiting payment',
        settled: 'Paid',
        totalsHeading: 'Amount due',
    },
    quotation: {
        dueDate: 'Valid until',
        open: 'Awaiting reply',
        settled: 'Accepted',
        totalsHeading: 'Quoted total',
    },
};

export type DocumentTotals = {
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
};

/**
 * Read an amount typed in major units as whole cents, mirroring the server.
 */
export function toCents(amount: string | number): number {
    const parsed =
        typeof amount === 'number' ? amount : Number.parseFloat(amount);

    return Number.isFinite(parsed) ? Math.round(parsed * 100) : 0;
}

/**
 * Read a typed quantity, falling back to nothing billed.
 */
export function toQuantity(quantity: string | number): number {
    const parsed =
        typeof quantity === 'number' ? quantity : Number.parseFloat(quantity);

    return Number.isFinite(parsed) ? parsed : 0;
}

/**
 * Multiply a line out, rounding the way the server does.
 */
export function lineTotalCents(line: DocumentLineDraft): number {
    return Math.round(toQuantity(line.quantity) * toCents(line.unit_price));
}

/**
 * Add a draft up, so the form shows the same totals the server will store.
 */
export function calculateTotals(
    lines: DocumentLineDraft[],
    taxRate: string | number,
    discount: string | number,
): DocumentTotals {
    const subtotal = lines.reduce(
        (total, line) => total + lineTotalCents(line),
        0,
    );
    const discountCents = Math.max(toCents(discount), 0);
    const taxable = Math.max(0, subtotal - discountCents);
    const tax = Math.round((taxable * toQuantity(taxRate)) / 100);

    return {
        subtotal,
        discount: discountCents,
        tax,
        total: taxable + tax,
    };
}

/**
 * Format an amount held in cents in the currency of its document.
 */
export function formatMoney(cents: number, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(cents / 100);
}
