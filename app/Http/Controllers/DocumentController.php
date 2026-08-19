<?php

namespace App\Http\Controllers;

use App\Actions\Documents\CreateDocument;
use App\Actions\Documents\GenerateDocumentNumber;
use App\Actions\Documents\SummarizeDocuments;
use App\Actions\Documents\UpdateDocument;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Http\Requests\Documents\UpdateDocumentRequest;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    /**
     * The number of days an invoice is payable, and a quotation stays valid, by default.
     */
    protected const int INVOICE_TERM_IN_DAYS = 14;

    protected const int QUOTATION_TERM_IN_DAYS = 30;

    /**
     * Show the invoices or quotations of the user.
     */
    public function index(Request $request, SummarizeDocuments $summarize): Response
    {
        $user = $request->user();

        $type = $this->requestedType($request);
        $search = $request->string('search')->trim()->toString() ?: null;
        $status = DocumentStatus::tryFrom($request->string('status')->toString());
        $status = $status?->appliesTo($type) === true ? $status : null;

        $documents = Document::whereBelongsTo($user)
            ->ofType($type)
            ->when($search, fn (Builder $query, string $search) => $query->search($search))
            ->when($status, fn (Builder $query, DocumentStatus $status) => $query->withStatus($status))
            ->with('items')
            ->orderByIssueDate()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('documents/index', [
            'documents' => $documents,
            'stats' => $summarize->handle($user, $type),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'type' => $type,
            'types' => DocumentType::values(),
            'statuses' => $type->statusValues(),
        ]);
    }

    /**
     * Show the form for drafting a new invoice or quotation.
     */
    public function create(Request $request, GenerateDocumentNumber $generateNumber): Response
    {
        $type = $this->requestedType($request);

        return Inertia::render('documents/create', [
            'type' => $type,
            'statuses' => $type->statusValues(),
            'currencies' => Document::CURRENCIES,
            'nextNumber' => $generateNumber->handle($request->user(), $type),
            'defaults' => [
                'issue_date' => today()->toDateString(),
                'due_date' => today()->addDays($type === DocumentType::Invoice
                    ? self::INVOICE_TERM_IN_DAYS
                    : self::QUOTATION_TERM_IN_DAYS)->toDateString(),
                'currency' => Document::DEFAULT_CURRENCY,
            ],
        ]);
    }

    /**
     * Store a newly drafted invoice or quotation.
     */
    public function store(StoreDocumentRequest $request, CreateDocument $createDocument): RedirectResponse
    {
        $attributes = $request->validated();
        unset($attributes['items']);

        /** @var array<int, array{description: string, quantity: string, unit_price: string}> $items */
        $items = $request->validated('items');

        $document = $createDocument->handle($request->user(), $request->documentType(), $attributes, $items);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':number created.', ['number' => $document->number])]);

        return to_route('documents.index', ['type' => $document->type]);
    }

    /**
     * Show the form for editing the given invoice or quotation.
     */
    public function edit(Document $document): Response
    {
        Gate::authorize('view', $document);

        $document->load(['items', 'convertedFrom']);

        return Inertia::render('documents/edit', [
            'document' => $document,
            'statuses' => $document->type->statusValues(),
            'currencies' => Document::CURRENCIES,
        ]);
    }

    /**
     * Update the given invoice or quotation.
     */
    public function update(UpdateDocumentRequest $request, Document $document, UpdateDocument $updateDocument): RedirectResponse
    {
        Gate::authorize('update', $document);

        $attributes = $request->validated();
        unset($attributes['items']);

        /** @var array<int, array{description: string, quantity: string, unit_price: string}> $items */
        $items = $request->validated('items');

        $updateDocument->handle($document, $attributes, $items);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':number saved.', ['number' => $document->number])]);

        return back();
    }

    /**
     * Delete the given invoice or quotation.
     */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':number deleted.', ['number' => $document->number])]);

        return back();
    }

    /**
     * Resolve the document type the request is about, falling back to invoices.
     */
    protected function requestedType(Request $request): DocumentType
    {
        return DocumentType::tryFrom($request->string('type')->toString()) ?? DocumentType::Invoice;
    }
}
