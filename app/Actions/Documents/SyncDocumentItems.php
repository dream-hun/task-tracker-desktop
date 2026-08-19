<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;

final class SyncDocumentItems
{
    /**
     * Replace the lines of the document with the given ones, keeping their order.
     *
     * @param  array<int, array{description: string, quantity: float|int|string, unit_price: float|int|string}>  $items
     */
    public function handle(Document $document, array $items): void
    {
        $document->items()->delete();

        $document->items()->createMany(
            array_map(fn (array $item, int $position): array => [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'position' => $position,
            ], $items, array_keys($items)),
        );

        $document->load('items');
    }
}
