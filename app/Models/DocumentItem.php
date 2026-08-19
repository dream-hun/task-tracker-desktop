<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DocumentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $document_id
 * @property string $description
 * @property numeric-string $quantity
 * @property int $unit_price_cents
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-write float|int|string $unit_price
 * @property-read int $total_cents
 * @property-read Document $document
 */
#[Fillable(['description', 'quantity', 'unit_price', 'position'])]
#[Appends(['total_cents'])]
final class DocumentItem extends Model
{
    /** @use HasFactory<DocumentItemFactory> */
    use HasFactory;

    /**
     * Get the document the line belongs to.
     *
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price_cents' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Set the unit price from an amount in major currency units.
     *
     * @return Attribute<never, float|int|string>
     */
    protected function unitPrice(): Attribute
    {
        return Attribute::set(fn (float|int|string $value): array => [
            'unit_price_cents' => (int) round((float) $value * 100),
        ]);
    }

    /**
     * Calculate what the line adds up to.
     *
     * @return Attribute<int, never>
     */
    protected function totalCents(): Attribute
    {
        return Attribute::get(fn (): int => (int) round((float) $this->quantity * $this->unit_price_cents));
    }
}
