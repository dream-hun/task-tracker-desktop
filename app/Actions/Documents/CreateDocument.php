<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateDocument
{
    public function __construct(
        private GenerateDocumentNumber $generateNumber,
        private SyncDocumentItems $syncItems,
    ) {}

    /**
     * Create a document, numbering it and storing the lines it bills.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{description: string, quantity: float|int|string, unit_price: float|int|string}>  $items
     */
    public function handle(User $user, DocumentType $type, array $attributes, array $items): Document
    {
        return DB::transaction(function () use ($user, $attributes, $items, $type): Document {
            $document = $user->documents()->create([
                ...$attributes,
                'number' => $this->generateNumber->handle($user, $type),
            ]);

            $this->syncItems->handle($document, $items);

            return $document;
        });
    }
}
