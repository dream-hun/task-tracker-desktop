<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\User;

class SummarizeDocuments
{
    /**
     * Count and add up the documents of the user for the given type.
     *
     * Totals are derived from the billed lines, so the documents are summed in
     * PHP to stay consistent with what every other screen shows. Amounts are
     * only meaningful when every document is billed in the same currency, so
     * the currency they share is reported alongside them, or null when mixed.
     *
     * @return array{total: int, drafts: int, open: int, overdue: int, settled: int, open_cents: int, settled_cents: int, currency: string|null}
     */
    public function handle(User $user, DocumentType $type): array
    {
        $documents = $user->documents()->ofType($type)->with('items')->get();

        $open = $documents->filter(fn (Document $document): bool => $document->status->isOpen());
        $settled = $documents->filter(fn (Document $document): bool => $document->status->isSettled());

        return [
            'total' => $documents->count(),
            'drafts' => $documents->where('status', DocumentStatus::Draft)->count(),
            'open' => $open->count(),
            'overdue' => $open->filter(fn (Document $document): bool => $document->is_overdue)->count(),
            'settled' => $settled->count(),
            'open_cents' => (int) $open->sum(fn (Document $document): int => $document->total_cents),
            'settled_cents' => (int) $settled->sum(fn (Document $document): int => $document->total_cents),
            'currency' => $documents->pluck('currency')->unique()->count() === 1
                ? $documents->first()?->currency
                : null,
        ];
    }
}
