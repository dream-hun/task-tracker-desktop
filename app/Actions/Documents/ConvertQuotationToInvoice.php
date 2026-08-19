<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\DocumentItem;
use Illuminate\Support\Facades\DB;

final class ConvertQuotationToInvoice
{
    /**
     * The number of days a converted invoice is payable in.
     */
    public const int PAYMENT_TERM_IN_DAYS = 14;

    public function __construct(
        private GenerateDocumentNumber $generateNumber,
        private SyncDocumentItems $syncItems,
    ) {}

    /**
     * Draft an invoice from the quotation and mark the quotation as accepted.
     */
    public function handle(Document $quotation): Document
    {
        return DB::transaction(function () use ($quotation): Document {
            $invoice = new Document([
                'type' => DocumentType::Invoice,
                'status' => DocumentStatus::Draft,
                'number' => $this->generateNumber->handle($quotation->user, DocumentType::Invoice),
                'client_name' => $quotation->client_name,
                'client_email' => $quotation->client_email,
                'client_address' => $quotation->client_address,
                'issue_date' => today(),
                'due_date' => today()->addDays(self::PAYMENT_TERM_IN_DAYS),
                'currency' => $quotation->currency,
                'tax_rate' => $quotation->tax_rate,
                'discount' => $quotation->discount_cents / 100,
                'notes' => $quotation->notes,
            ]);

            $invoice->convertedFrom()->associate($quotation);

            $quotation->user->documents()->save($invoice);

            $this->syncItems->handle($invoice, $quotation->items->map(fn (DocumentItem $item): array => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price_cents / 100,
            ])->all());

            $quotation->update(['status' => DocumentStatus::Accepted]);

            return $invoice;
        });
    }
}
