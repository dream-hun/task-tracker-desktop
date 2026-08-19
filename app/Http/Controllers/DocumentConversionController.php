<?php

namespace App\Http\Controllers;

use App\Actions\Documents\ConvertQuotationToInvoice;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class DocumentConversionController extends Controller
{
    /**
     * Draft an invoice from the given quotation.
     */
    public function __invoke(Document $document, ConvertQuotationToInvoice $convert): RedirectResponse
    {
        Gate::authorize('update', $document);

        abort_unless($document->isQuotation(), Response::HTTP_NOT_FOUND);

        $document->load('items');

        $invoice = $convert->handle($document);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice :number drafted from :quotation.', [
            'number' => $invoice->number,
            'quotation' => $document->number,
        ])]);

        return to_route('documents.edit', $invoice);
    }
}
