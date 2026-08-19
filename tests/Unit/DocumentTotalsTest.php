<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\DocumentItem;
use Illuminate\Database\Eloquent\Collection;

function documentWithLines(array $lines, float $taxRate = 0, int $discountCents = 0): Document
{
    $document = new Document(['tax_rate' => $taxRate]);
    $document->discount_cents = $discountCents;

    $document->setRelation('items', new Collection(array_map(
        fn (array $line): DocumentItem => new DocumentItem([
            'description' => 'Line',
            'quantity' => $line[0],
            'unit_price' => $line[1],
        ]),
        $lines,
    )));

    return $document;
}

test('a line multiplies its quantity by its unit price', function () {
    $item = new DocumentItem(['description' => 'Line', 'quantity' => '7.5', 'unit_price' => '120']);

    expect($item->unit_price_cents)->toBe(12000);
    expect($item->total_cents)->toBe(90000);
});

test('a fractional line total is rounded to the nearest cent', function () {
    $item = new DocumentItem(['description' => 'Line', 'quantity' => '0.33', 'unit_price' => '10']);

    expect($item->total_cents)->toBe(330);
});

test('the subtotal adds up every line', function () {
    $document = documentWithLines([[7.5, 120], [1, 49.99]]);

    expect($document->subtotal_cents)->toBe(94999);
    expect($document->tax_cents)->toBe(0);
    expect($document->total_cents)->toBe(94999);
});

test('tax is charged over the discounted subtotal', function () {
    $document = documentWithLines([[7.5, 120], [1, 49.99]], taxRate: 18, discountCents: 2500);

    expect($document->tax_cents)->toBe(16650);
    expect($document->total_cents)->toBe(109149);
});

test('a discount never pushes the total below nothing', function () {
    $document = documentWithLines([[1, 100]], taxRate: 20, discountCents: 25000);

    expect($document->subtotal_cents)->toBe(10000);
    expect($document->tax_cents)->toBe(0);
    expect($document->total_cents)->toBe(0);
});
