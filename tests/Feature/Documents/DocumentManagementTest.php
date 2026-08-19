<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function documentPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'invoice',
        'status' => 'draft',
        'client_name' => 'Acme Industries',
        'client_email' => 'ap@acme.test',
        'client_address' => "12 Market Street\nSpringfield",
        'issue_date' => '2026-08-18',
        'due_date' => '2026-09-01',
        'currency' => 'EUR',
        'tax_rate' => '18',
        'discount' => '25',
        'notes' => 'Payable within 14 days.',
        'items' => [
            ['description' => 'Design work', 'quantity' => '7.5', 'unit_price' => '120'],
            ['description' => 'Hosting', 'quantity' => '1', 'unit_price' => '49.99'],
        ],
    ], $overrides);
}

test('the draft form suggests the next number for the chosen type', function () {
    $user = User::factory()->create();
    Document::factory()->for($user)->invoice()->create(['number' => sprintf('INV-%d-0007', today()->year)]);

    $response = $this->actingAs($user)->get(route('documents.create', ['type' => 'invoice']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('documents/create')
        ->where('type', 'invoice')
        ->where('nextNumber', sprintf('INV-%d-0008', today()->year))
        ->where('defaults.currency', 'USD')
        ->has('currencies')
    );
});

test('an invoice can be created with its lines', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('documents.create'))
        ->post(route('documents.store'), documentPayload());

    $response->assertSessionHasNoErrors()->assertRedirect(route('documents.index', ['type' => 'invoice']));

    $document = $user->documents()->with('items')->sole();

    expect($document->type)->toBe(DocumentType::Invoice);
    expect($document->status)->toBe(DocumentStatus::Draft);
    expect($document->number)->toBe(sprintf('INV-%d-0001', today()->year));
    expect($document->client_name)->toBe('Acme Industries');
    expect($document->currency)->toBe('EUR');
    expect($document->issue_date->toDateString())->toBe('2026-08-18');
    expect($document->due_date->toDateString())->toBe('2026-09-01');
    expect($document->discount_cents)->toBe(2500);
    expect($document->items)->toHaveCount(2);
    expect($document->items->first()->unit_price_cents)->toBe(12000);
    expect($document->items->first()->position)->toBe(0);
    expect($document->items->last()->unit_price_cents)->toBe(4999);
});

test('the totals of a document follow from its lines, discount and tax rate', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('documents.store'), documentPayload())->assertSessionHasNoErrors();

    $document = $user->documents()->with('items')->sole();

    // 7.5 × 120.00 + 1 × 49.99 = 949.99, less 25.00 discount, plus 18% tax.
    expect($document->subtotal_cents)->toBe(94999);
    expect($document->tax_cents)->toBe(16650);
    expect($document->total_cents)->toBe(109149);
});

test('documents are numbered per user and per type', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->post(route('documents.store'), documentPayload())->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('documents.store'), documentPayload())->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('documents.store'), documentPayload(['type' => 'quotation', 'status' => 'draft']))->assertSessionHasNoErrors();
    $this->actingAs($other)->post(route('documents.store'), documentPayload())->assertSessionHasNoErrors();

    $year = today()->year;

    expect($user->documents()->pluck('number')->all())->toBe([
        sprintf('INV-%d-0001', $year),
        sprintf('INV-%d-0002', $year),
        sprintf('QUO-%d-0001', $year),
    ]);

    expect($other->documents()->pluck('number')->all())->toBe([sprintf('INV-%d-0001', $year)]);
});

test('creating a document validates the submitted details', function (array $payload, string $invalidField) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('documents.create'))
        ->post(route('documents.store'), documentPayload($payload));

    $response->assertSessionHasErrors($invalidField);

    expect($user->documents()->count())->toBe(0);
})->with([
    'unknown type' => [['type' => 'receipt'], 'type'],
    'missing client' => [['client_name' => ''], 'client_name'],
    'invalid client email' => [['client_email' => 'not-an-email'], 'client_email'],
    'missing issue date' => [['issue_date' => ''], 'issue_date'],
    'due date before issue date' => [['due_date' => '2026-08-17'], 'due_date'],
    'unsupported currency' => [['currency' => 'XYZ'], 'currency'],
    'tax rate above a hundred' => [['tax_rate' => '120'], 'tax_rate'],
    'negative discount' => [['discount' => '-5'], 'discount'],
    'status of the other type' => [['status' => 'accepted'], 'status'],
    'no lines' => [['items' => []], 'items'],
    'line without description' => [['items' => [['description' => '', 'quantity' => '1', 'unit_price' => '10']]], 'items.0.description'],
    'line without quantity' => [['items' => [['description' => 'Work', 'quantity' => '0', 'unit_price' => '10']]], 'items.0.quantity'],
    'line with a negative price' => [['items' => [['description' => 'Work', 'quantity' => '1', 'unit_price' => '-10']]], 'items.0.unit_price'],
]);

test('a quotation can only use statuses of its own type', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('documents.store'), documentPayload(['type' => 'quotation', 'status' => 'accepted']))
        ->assertSessionHasNoErrors();

    expect($user->documents()->sole()->status)->toBe(DocumentStatus::Accepted);

    $this
        ->actingAs($user)
        ->post(route('documents.store'), documentPayload(['type' => 'quotation', 'status' => 'paid']))
        ->assertSessionHasErrors('status');
});

test('a document can be edited', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->invoice()->create();
    DocumentItem::factory()->for($document)->billing(1, 10)->create();

    $response = $this->actingAs($user)->get(route('documents.edit', $document));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('documents/edit')
        ->where('document.id', $document->id)
        ->has('document.items', 1)
        ->has('statuses')
    );
});

test('a document can be updated and its lines replaced', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->invoice()->draft()->create(['client_name' => 'Old Client']);
    $removed = DocumentItem::factory()->for($document)->billing(1, 10)->create();

    $response = $this
        ->actingAs($user)
        ->from(route('documents.edit', $document))
        ->put(route('documents.update', $document), documentPayload([
            'client_name' => 'New Client',
            'status' => 'sent',
            'items' => [['description' => 'Consulting', 'quantity' => '3', 'unit_price' => '200']],
        ]));

    $response->assertSessionHasNoErrors()->assertRedirect(route('documents.edit', $document));

    $document->refresh()->load('items');

    expect($document->client_name)->toBe('New Client');
    expect($document->status)->toBe(DocumentStatus::Sent);
    expect($document->items)->toHaveCount(1);
    expect($document->items->sole()->description)->toBe('Consulting');

    // 3 × 200.00 = 600.00, less 25.00 discount, plus 18% tax.
    expect($document->total_cents)->toBe(67850);

    $this->assertModelMissing($removed);
});

test('the type and number of a document do not change when it is updated', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->quotation()->draft()->create(['number' => 'QUO-2026-0003']);

    $this
        ->actingAs($user)
        ->put(route('documents.update', $document), documentPayload([
            'type' => 'invoice',
            'status' => 'sent',
            'number' => 'INV-2026-9999',
        ]))
        ->assertSessionHasNoErrors();

    $document->refresh();

    expect($document->type)->toBe(DocumentType::Quotation);
    expect($document->number)->toBe('QUO-2026-0003');
});

test('a document cannot be edited or updated by another user', function () {
    $document = Document::factory()->invoice()->create(['client_name' => 'Not Yours']);

    $this->actingAs(User::factory()->create())->get(route('documents.edit', $document))->assertForbidden();

    $this
        ->actingAs(User::factory()->create())
        ->put(route('documents.update', $document), documentPayload())
        ->assertForbidden();

    expect($document->refresh()->client_name)->toBe('Not Yours');
});

test('a document can be deleted with its lines', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->invoice()->create();
    $item = DocumentItem::factory()->for($document)->create();

    $response = $this
        ->actingAs($user)
        ->from(route('documents.index'))
        ->delete(route('documents.destroy', $document));

    $response->assertSessionHasNoErrors()->assertRedirect(route('documents.index'));

    $this->assertModelMissing($document);
    $this->assertModelMissing($item);
});

test('a document cannot be deleted by another user', function () {
    $document = Document::factory()->invoice()->create();

    $this
        ->actingAs(User::factory()->create())
        ->delete(route('documents.destroy', $document))
        ->assertForbidden();

    $this->assertModelExists($document);
});

test('deleting a user deletes their documents', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->invoice()->create();
    $item = DocumentItem::factory()->for($document)->create();

    $user->delete();

    $this->assertModelMissing($document);
    $this->assertModelMissing($item);
});
