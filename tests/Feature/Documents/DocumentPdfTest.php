<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\User;

test('a document can be downloaded as a pdf', function () {
    $user = User::factory()->create(['name' => 'Jane Freelancer']);
    $invoice = Document::factory()->for($user)->invoice()->create([
        'number' => 'INV-2026-0007',
        'tax_rate' => 18,
        'discount' => 25,
    ]);
    DocumentItem::factory()->for($invoice)->billing(7.5, 120)->create(['description' => 'Design work']);

    $response = $this->actingAs($user)->get(route('documents.pdf', $invoice));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $response->assertDownload('INV-2026-0007.pdf');

    expect($response->content())->toStartWith('%PDF-');
});

test('a quotation is rendered as its own kind of document', function () {
    $user = User::factory()->create();
    $quotation = Document::factory()->for($user)->quotation()->create(['number' => 'QUO-2026-0002']);
    DocumentItem::factory()->for($quotation)->billing(1, 500)->create();

    $response = $this->actingAs($user)->get(route('documents.pdf', $quotation));

    $response->assertOk()->assertDownload('QUO-2026-0002.pdf');
});

test('a document with many lines and unusual characters still renders', function () {
    $user = User::factory()->create(['name' => 'Ünicode Studio']);
    $invoice = Document::factory()->for($user)->invoice()->currency('EUR')->create([
        'client_name' => 'Société Générale',
        'client_address' => "12 Rue de la Paix\n75002 Paris",
        'notes' => "Merci beaucoup.\nPayable within 14 days.",
    ]);
    DocumentItem::factory()->for($invoice)->count(40)->create();

    $response = $this->actingAs($user)->get(route('documents.pdf', $invoice));

    $response->assertOk();

    expect(mb_strlen($response->content()))->toBeGreaterThan(1000);
});

test('a document of another user cannot be downloaded', function () {
    $document = Document::factory()->invoice()->create();

    $this
        ->actingAs(User::factory()->create())
        ->get(route('documents.pdf', $document))
        ->assertForbidden();
});

test('guests are redirected to the login page', function () {
    $document = Document::factory()->invoice()->create();

    $this->get(route('documents.pdf', $document))->assertRedirect(route('login'));
});
