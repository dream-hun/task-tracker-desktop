<?php

declare(strict_types=1);

use App\Actions\Documents\ConvertQuotationToInvoice;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\User;

test('a quotation can be converted into a draft invoice', function (): void {
    $user = User::factory()->create();
    $quotation = Document::factory()->for($user)->quotation()->sent()->create([
        'number' => 'QUO-2026-0003',
        'client_name' => 'Acme Industries',
        'client_email' => 'ap@acme.test',
        'currency' => 'EUR',
        'tax_rate' => 18,
        'discount' => 25,
        'notes' => 'Valid for 30 days.',
    ]);
    DocumentItem::factory()->for($quotation)->billing(7.5, 120)->create(['description' => 'Design work']);
    DocumentItem::factory()->for($quotation)->billing(1, 49.99)->create(['description' => 'Hosting']);

    $response = $this
        ->actingAs($user)
        ->from(route('documents.index', ['type' => 'quotation']))
        ->post(route('documents.convert', $quotation));

    $invoice = $user->documents()->where('type', DocumentType::Invoice)->with('items')->sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('documents.edit', $invoice));

    expect($invoice->number)->toBe(sprintf('INV-%d-0001', today()->year));
    expect($invoice->status)->toBe(DocumentStatus::Draft);
    expect($invoice->converted_from_id)->toBe($quotation->id);
    expect($invoice->client_name)->toBe('Acme Industries');
    expect($invoice->currency)->toBe('EUR');
    expect($invoice->discount_cents)->toBe(2500);
    expect($invoice->issue_date->toDateString())->toBe(today()->toDateString());
    expect($invoice->due_date->toDateString())->toBe(today()->addDays(ConvertQuotationToInvoice::PAYMENT_TERM_IN_DAYS)->toDateString());
    expect($invoice->items->pluck('description')->all())->toBe(['Design work', 'Hosting']);
    expect($invoice->total_cents)->toBe(109149);
});

test('converting a quotation marks it as accepted', function (): void {
    $user = User::factory()->create();
    $quotation = Document::factory()->for($user)->quotation()->sent()->create();
    DocumentItem::factory()->for($quotation)->billing(1, 100)->create();

    $this->actingAs($user)->post(route('documents.convert', $quotation))->assertSessionHasNoErrors();

    expect($quotation->refresh()->status)->toBe(DocumentStatus::Accepted);
});

test('an invoice cannot be converted', function (): void {
    $user = User::factory()->create();
    $invoice = Document::factory()->for($user)->invoice()->sent()->create();

    $this->actingAs($user)->post(route('documents.convert', $invoice))->assertNotFound();

    expect($user->documents()->count())->toBe(1);
});

test('a quotation of another user cannot be converted', function (): void {
    $quotation = Document::factory()->quotation()->sent()->create();

    $this
        ->actingAs(User::factory()->create())
        ->post(route('documents.convert', $quotation))
        ->assertForbidden();

    expect(Document::query()->count())->toBe(1);
});
