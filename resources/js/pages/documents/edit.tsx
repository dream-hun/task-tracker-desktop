import { Head, setLayoutProps } from '@inertiajs/react';
import DocumentForm from '@/components/document-form';
import { edit, index } from '@/routes/documents';
import type { BillingDocument, DocumentStatus } from '@/types';

type DocumentsEditProps = {
    document: BillingDocument;
    statuses: DocumentStatus[];
    currencies: string[];
};

export default function DocumentsEdit({
    document,
    statuses,
    currencies,
}: DocumentsEditProps) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Billing',
                href: index({ query: { type: document.type } }),
            },
            { title: document.number, href: edit(document.id) },
        ],
    });

    return (
        <>
            <Head title={document.number} />

            <DocumentForm
                type={document.type}
                document={document}
                statuses={statuses}
                currencies={currencies}
            />
        </>
    );
}
