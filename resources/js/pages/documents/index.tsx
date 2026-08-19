import { Head, Link } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';
import DocumentFilters from '@/components/document-filters';
import DocumentListItem from '@/components/document-list-item';
import DocumentStats from '@/components/document-stats';
import { Button } from '@/components/ui/button';
import { documentKindLabels, documentKindPluralLabels } from '@/lib/documents';
import { create, index } from '@/routes/documents';
import type {
    BillingDocument,
    DocumentFilters as Filters,
    DocumentKind,
    DocumentStats as Stats,
    DocumentStatus,
    Paginated,
} from '@/types';

type DocumentsIndexProps = {
    documents: Paginated<BillingDocument>;
    stats: Stats;
    filters: Filters;
    type: DocumentKind;
    types: DocumentKind[];
    statuses: DocumentStatus[];
};

export default function DocumentsIndex({
    documents,
    stats,
    filters,
    type,
    types,
    statuses,
}: DocumentsIndexProps) {
    const hasFilters = filters.search !== null || filters.status !== null;
    const plural = documentKindPluralLabels[type].toLowerCase();

    return (
        <>
            <Head title={documentKindPluralLabels[type]} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-0.5">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Billing
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Draft {plural}, keep track of what is owed, and
                            print anything you need to send.
                        </p>
                    </div>

                    <Button asChild data-test="new-document-button">
                        <Link href={create({ query: { type } })}>
                            <Plus className="size-4" />
                            New {documentKindLabels[type].toLowerCase()}
                        </Link>
                    </Button>
                </div>

                <DocumentStats stats={stats} type={type} />

                <DocumentFilters
                    filters={filters}
                    type={type}
                    types={types}
                    statuses={statuses}
                />

                {documents.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border">
                        <FileText className="size-8 text-muted-foreground" />
                        <p className="font-medium">
                            {hasFilters
                                ? `No ${plural} match these filters`
                                : `No ${plural} yet`}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {hasFilters
                                ? 'Try a different search or clear the filters.'
                                : `Draft your first ${documentKindLabels[type].toLowerCase()} to get started.`}
                        </p>
                    </div>
                ) : (
                    <div className="flex flex-col gap-3">
                        {documents.data.map((document) => (
                            <DocumentListItem
                                key={document.id}
                                document={document}
                                statuses={statuses}
                            />
                        ))}
                    </div>
                )}

                {documents.last_page > 1 && (
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-sm text-muted-foreground">
                            Showing {documents.from}–{documents.to} of{' '}
                            {documents.total} {plural}
                        </p>

                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={documents.prev_page_url === null}
                                asChild={documents.prev_page_url !== null}
                            >
                                {documents.prev_page_url === null ? (
                                    'Previous'
                                ) : (
                                    <Link
                                        href={documents.prev_page_url}
                                        preserveScroll
                                    >
                                        Previous
                                    </Link>
                                )}
                            </Button>

                            <Button
                                variant="outline"
                                size="sm"
                                disabled={documents.next_page_url === null}
                                asChild={documents.next_page_url !== null}
                            >
                                {documents.next_page_url === null ? (
                                    'Next'
                                ) : (
                                    <Link
                                        href={documents.next_page_url}
                                        preserveScroll
                                    >
                                        Next
                                    </Link>
                                )}
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

DocumentsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Billing',
            href: index(),
        },
    ],
};
