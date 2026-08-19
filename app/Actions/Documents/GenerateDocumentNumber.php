<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentType;
use App\Models\User;
use Illuminate\Support\Str;

final class GenerateDocumentNumber
{
    /**
     * Build the next sequential number for the user, such as "INV-2026-0007".
     */
    public function handle(User $user, DocumentType $type): string
    {
        $prefix = $type->prefix().'-'.today()->year;

        $latest = $user->documents()
            ->where('number', 'like', "{$prefix}-%")
            ->max('number');

        $sequence = is_string($latest) ? ((int) Str::afterLast($latest, '-')) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $sequence);
    }
}
