import { Head, setLayoutProps } from '@inertiajs/react';
import DocumentForm from '@/components/document-form';
import { documentKindLabels } from '@/lib/documents';
import { create, index } from '@/routes/documents';
import type { DocumentDefaults, DocumentKind, DocumentStatus } from '@/types';

type DocumentsCreateProps = {
    type: DocumentKind;
    statuses: DocumentStatus[];
    currencies: string[];
    nextNumber: string;
    defaults: DocumentDefaults;
};

export default function DocumentsCreate({
    type,
    statuses,
    currencies,
    nextNumber,
    defaults,
}: DocumentsCreateProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Billing', href: index({ query: { type } }) },
            {
                title: `New ${documentKindLabels[type].toLowerCase()}`,
                href: create({ query: { type } }),
            },
        ],
    });

    return (
        <>
            <Head title={`New ${documentKindLabels[type].toLowerCase()}`} />

            <DocumentForm
                type={type}
                statuses={statuses}
                currencies={currencies}
                nextNumber={nextNumber}
                defaults={defaults}
            />
        </>
    );
}
