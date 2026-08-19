export type DocumentKind = 'invoice' | 'quotation';

export type DocumentStatus =
    'draft' | 'sent' | 'accepted' | 'declined' | 'paid' | 'cancelled';

export type DocumentLine = {
    id: number;
    document_id: number;
    description: string;
    quantity: string;
    unit_price_cents: number;
    position: number;
    total_cents: number;
};

export type BillingDocument = {
    id: number;
    user_id: number;
    converted_from_id: number | null;
    type: DocumentKind;
    status: DocumentStatus;
    number: string;
    client_name: string;
    client_email: string | null;
    client_address: string | null;
    issue_date: string;
    due_date: string | null;
    currency: string;
    tax_rate: string;
    discount_cents: number;
    notes: string | null;
    subtotal_cents: number;
    tax_cents: number;
    total_cents: number;
    is_overdue: boolean;
    items: DocumentLine[];
    converted_from?: BillingDocument | null;
    created_at: string;
    updated_at: string;
};

export type DocumentFilters = {
    search: string | null;
    status: DocumentStatus | null;
};

export type DocumentStats = {
    total: number;
    drafts: number;
    open: number;
    overdue: number;
    settled: number;
    open_cents: number;
    settled_cents: number;
    currency: string | null;
};

export type DocumentDefaults = {
    issue_date: string;
    due_date: string;
    currency: string;
};

export type DocumentIssuer = {
    name: string;
    email: string;
};

export type DocumentLineDraft = {
    description: string;
    quantity: string;
    unit_price: string;
};
