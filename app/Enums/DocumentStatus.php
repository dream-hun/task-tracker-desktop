<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * Get the name this status is known by.
     */
    public function label(): string
    {
        return Str::headline($this->value);
    }

    /**
     * Determine whether the status can be used by documents of the given type.
     */
    public function appliesTo(DocumentType $type): bool
    {
        return match ($this) {
            self::Accepted, self::Declined => $type === DocumentType::Quotation,
            self::Paid => $type === DocumentType::Invoice,
            self::Draft, self::Sent, self::Cancelled => true,
        };
    }

    /**
     * Determine whether the document is still waiting on the client.
     */
    public function isOpen(): bool
    {
        return $this === self::Sent;
    }

    /**
     * Determine whether the document was paid or accepted.
     */
    public function isSettled(): bool
    {
        return $this === self::Paid || $this === self::Accepted;
    }

    /**
     * Get every status value.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
