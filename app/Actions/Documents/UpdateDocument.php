<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

final readonly class UpdateDocument
{
    public function __construct(private SyncDocumentItems $syncItems) {}

    /**
     * Update a document and replace the lines it bills.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{description: string, quantity: float|int|string, unit_price: float|int|string}>  $items
     */
    public function handle(Document $document, array $attributes, array $items): Document
    {
        return DB::transaction(function () use ($document, $attributes, $items): Document {
            $document->update($attributes);

            $this->syncItems->handle($document, $items);

            return $document;
        });
    }
}
