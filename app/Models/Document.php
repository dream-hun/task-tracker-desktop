<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $converted_from_id
 * @property DocumentType $type
 * @property DocumentStatus $status
 * @property string $number
 * @property string $client_name
 * @property string|null $client_email
 * @property string|null $client_address
 * @property CarbonImmutable $issue_date
 * @property CarbonImmutable|null $due_date
 * @property string $currency
 * @property numeric-string $tax_rate
 * @property int $discount_cents
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-write float|int|string $discount
 * @property-read int $subtotal_cents
 * @property-read int $tax_cents
 * @property-read int $total_cents
 * @property-read bool $is_overdue
 * @property-read Collection<int, DocumentItem> $items
 * @property-read Document|null $convertedFrom
 * @property-read User $user
 */
#[Fillable([
    'type',
    'status',
    'number',
    'client_name',
    'client_email',
    'client_address',
    'issue_date',
    'due_date',
    'currency',
    'tax_rate',
    'discount',
    'notes',
])]
#[Appends(['subtotal_cents', 'tax_cents', 'total_cents', 'is_overdue'])]
final class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * The currency documents are billed in until the user picks another one.
     */
    public const string DEFAULT_CURRENCY = 'USD';

    /**
     * The currencies a document can be billed in.
     *
     * @var list<string>
     */
    public const array CURRENCIES = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'INR', 'KES', 'NGN', 'RWF', 'ZAR'];

    /**
     * The model's default attribute values.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => DocumentStatus::Draft->value,
        'currency' => self::DEFAULT_CURRENCY,
    ];

    /**
     * Get the user the document belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the quotation this document was converted from.
     *
     * @return BelongsTo<Document, $this>
     */
    public function convertedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'converted_from_id');
    }

    /**
     * Get the billed lines of the document.
     *
     * @return HasMany<DocumentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('position');
    }

    /**
     * Determine whether the document is a quotation.
     */
    public function isQuotation(): bool
    {
        return $this->type === DocumentType::Quotation;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'issue_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'tax_rate' => 'decimal:2',
            'discount_cents' => 'integer',
        ];
    }

    /**
     * Set the discount from an amount in major currency units.
     *
     * @return Attribute<never, float|int|string>
     */
    protected function discount(): Attribute
    {
        return Attribute::set(fn (float|int|string $value): array => [
            'discount_cents' => (int) round((float) $value * 100),
        ]);
    }

    /**
     * Sum up the lines of the document, before discount and tax.
     *
     * @return Attribute<int, never>
     */
    protected function subtotalCents(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->items->sum(
            fn (DocumentItem $item): int => $item->total_cents,
        ));
    }

    /**
     * Calculate the tax owed over the discounted subtotal.
     *
     * @return Attribute<int, never>
     */
    protected function taxCents(): Attribute
    {
        return Attribute::get(fn (): int => (int) round(
            $this->taxableCents() * (float) $this->tax_rate / 100,
        ));
    }

    /**
     * Calculate what the client owes in total.
     *
     * @return Attribute<int, never>
     */
    protected function totalCents(): Attribute
    {
        return Attribute::get(fn (): int => $this->taxableCents() + $this->tax_cents);
    }

    /**
     * Determine whether the document is still open and past its due date.
     *
     * @return Attribute<bool, never>
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn (): bool => $this->status->isOpen()
            && $this->due_date !== null
            && $this->due_date->isBefore(today()));
    }

    /**
     * Get the amount that is taxed, never less than nothing.
     */
    protected function taxableCents(): int
    {
        return max(0, $this->subtotal_cents - $this->discount_cents);
    }

    /**
     * Scope the query to documents matching the given search term.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(function (Builder $query) use ($term): void {
            $query->where('number', 'like', "%{$term}%")
                ->orWhere('client_name', 'like', "%{$term}%")
                ->orWhere('client_email', 'like', "%{$term}%");
        });
    }

    /**
     * Scope the query to documents of the given type.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, DocumentType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope the query to documents with the given status.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, DocumentStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope the query to documents that are still waiting on the client.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', DocumentStatus::Sent);
    }

    /**
     * Scope the query to open documents that are past their due date.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->open()->whereDate('due_date', '<', today());
    }

    /**
     * Scope the query to show the most recently issued documents first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function orderByIssueDate(Builder $query): void
    {
        $query->latest('issue_date')->orderByDesc('id');
    }
}
