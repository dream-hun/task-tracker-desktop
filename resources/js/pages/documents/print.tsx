import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/dates';
import {
    documentKindLabels,
    documentStatusLabels,
    documentWording,
    formatMoney,
} from '@/lib/documents';
import { index, pdf } from '@/routes/documents';
import type { BillingDocument, DocumentIssuer } from '@/types';

type DocumentsPrintProps = {
    document: BillingDocument;
    issuer: DocumentIssuer;
};

export default function DocumentsPrint({
    document,
    issuer,
}: DocumentsPrintProps) {
    const wording = documentWording[document.type];

    return (
        <div className="min-h-screen bg-neutral-100 py-8 print:bg-white print:py-0">
            <Head title={document.number} />

            <div className="mx-auto flex max-w-3xl items-center justify-between gap-4 px-6 pb-6 print:hidden">
                <Button variant="outline" asChild>
                    <Link href={index({ query: { type: document.type } })}>
                        <ArrowLeft className="size-4" />
                        Back to billing
                    </Link>
                </Button>

                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        onClick={() => window.print()}
                        data-test="print-button"
                    >
                        <Printer className="size-4" />
                        Print
                    </Button>

                    <Button asChild data-test="download-pdf-button">
                        <a href={pdf(document.id).url}>
                            <Download className="size-4" />
                            Download PDF
                        </a>
                    </Button>
                </div>
            </div>

            <article className="mx-auto max-w-3xl bg-white px-10 py-12 text-neutral-900 shadow-sm print:max-w-none print:px-0 print:py-0 print:shadow-none">
                <header className="flex flex-wrap items-start justify-between gap-6 border-b border-neutral-200 pb-8">
                    <div>
                        <h1 className="text-3xl font-semibold tracking-tight uppercase">
                            {documentKindLabels[document.type]}
                        </h1>
                        <p className="mt-1 text-sm text-neutral-500 tabular-nums">
                            {document.number}
                        </p>
                        <p className="mt-3 text-xs font-medium tracking-wide text-neutral-500 uppercase">
                            {documentStatusLabels[document.status]}
                        </p>
                    </div>

                    <div className="text-right text-sm">
                        <p className="font-medium">{issuer.name}</p>
                        <p className="text-neutral-500">{issuer.email}</p>
                    </div>
                </header>

                <section className="grid gap-8 py-8 sm:grid-cols-2">
                    <div className="space-y-1 text-sm">
                        <h2 className="text-xs font-medium tracking-wide text-neutral-500 uppercase">
                            Billed to
                        </h2>
                        <p className="font-medium">{document.client_name}</p>
                        {document.client_email && (
                            <p className="text-neutral-600">
                                {document.client_email}
                            </p>
                        )}
                        {document.client_address && (
                            <p className="whitespace-pre-line text-neutral-600">
                                {document.client_address}
                            </p>
                        )}
                    </div>

                    <div className="space-y-1 text-sm sm:text-right">
                        <div className="flex justify-between gap-4 sm:justify-end">
                            <span className="text-neutral-500">Issue date</span>
                            <span className="tabular-nums">
                                {formatDate(document.issue_date)}
                            </span>
                        </div>

                        {document.due_date && (
                            <div className="flex justify-between gap-4 sm:justify-end">
                                <span className="text-neutral-500">
                                    {wording.dueDate}
                                </span>
                                <span className="tabular-nums">
                                    {formatDate(document.due_date)}
                                </span>
                            </div>
                        )}

                        <div className="flex justify-between gap-4 sm:justify-end">
                            <span className="text-neutral-500">Currency</span>
                            <span>{document.currency}</span>
                        </div>
                    </div>
                </section>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-y border-neutral-200 text-left text-xs tracking-wide text-neutral-500 uppercase">
                            <th className="py-2 font-medium">Description</th>
                            <th className="py-2 text-right font-medium">Qty</th>
                            <th className="py-2 text-right font-medium">
                                Unit price
                            </th>
                            <th className="py-2 text-right font-medium">
                                Amount
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        {document.items.map((item) => (
                            <tr
                                key={item.id}
                                className="border-b border-neutral-100"
                            >
                                <td className="py-3 pr-4">
                                    {item.description}
                                </td>
                                <td className="py-3 text-right tabular-nums">
                                    {Number(item.quantity)}
                                </td>
                                <td className="py-3 text-right tabular-nums">
                                    {formatMoney(
                                        item.unit_price_cents,
                                        document.currency,
                                    )}
                                </td>
                                <td className="py-3 text-right tabular-nums">
                                    {formatMoney(
                                        item.total_cents,
                                        document.currency,
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                <section className="mt-6 flex justify-end">
                    <dl className="w-full max-w-xs space-y-2 text-sm">
                        <div className="flex justify-between gap-4">
                            <dt className="text-neutral-500">Subtotal</dt>
                            <dd className="tabular-nums">
                                {formatMoney(
                                    document.subtotal_cents,
                                    document.currency,
                                )}
                            </dd>
                        </div>

                        {document.discount_cents > 0 && (
                            <div className="flex justify-between gap-4">
                                <dt className="text-neutral-500">Discount</dt>
                                <dd className="tabular-nums">
                                    −
                                    {formatMoney(
                                        document.discount_cents,
                                        document.currency,
                                    )}
                                </dd>
                            </div>
                        )}

                        <div className="flex justify-between gap-4">
                            <dt className="text-neutral-500">
                                Tax ({Number(document.tax_rate)}%)
                            </dt>
                            <dd className="tabular-nums">
                                {formatMoney(
                                    document.tax_cents,
                                    document.currency,
                                )}
                            </dd>
                        </div>

                        <div className="flex justify-between gap-4 border-t border-neutral-200 pt-2 text-base font-semibold">
                            <dt>{wording.totalsHeading}</dt>
                            <dd className="tabular-nums">
                                {formatMoney(
                                    document.total_cents,
                                    document.currency,
                                )}
                            </dd>
                        </div>
                    </dl>
                </section>

                {document.notes && (
                    <section className="mt-10 border-t border-neutral-200 pt-6 text-sm">
                        <h2 className="text-xs font-medium tracking-wide text-neutral-500 uppercase">
                            Notes
                        </h2>
                        <p className="mt-2 whitespace-pre-line text-neutral-700">
                            {document.notes}
                        </p>
                    </section>
                )}
            </article>
        </div>
    );
}
