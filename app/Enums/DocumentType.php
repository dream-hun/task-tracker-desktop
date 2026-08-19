<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentType: string
{
    case Invoice = 'invoice';
    case Quotation = 'quotation';

    /**
     * Get every type value.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the name this type is known by.
     */
    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Invoice',
            self::Quotation => 'Quotation',
        };
    }

    /**
     * Get what the due date of this type means to the client.
     */
    public function dueDateLabel(): string
    {
        return match ($this) {
            self::Invoice => 'Due date',
            self::Quotation => 'Valid until',
        };
    }

    /**
     * Get what the total of this type means to the client.
     */
    public function totalLabel(): string
    {
        return match ($this) {
            self::Invoice => 'Amount due',
            self::Quotation => 'Quoted total',
        };
    }

    /**
     * Get the prefix used when numbering documents of this type.
     */
    public function prefix(): string
    {
        return match ($this) {
            self::Invoice => 'INV',
            self::Quotation => 'QUO',
        };
    }

    /**
     * Get the statuses a document of this type can be in.
     *
     * @return array<int, DocumentStatus>
     */
    public function statuses(): array
    {
        return array_values(array_filter(
            DocumentStatus::cases(),
            fn (DocumentStatus $status): bool => $status->appliesTo($this),
        ));
    }

    /**
     * Get the values of the statuses a document of this type can be in.
     *
     * @return array<int, string>
     */
    public function statusValues(): array
    {
        return array_map(fn (DocumentStatus $status): string => $status->value, $this->statuses());
    }
}
