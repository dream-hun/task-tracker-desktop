<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('a document can be printed', function (): void {
    $user = User::factory()->create(['name' => 'Jane Freelancer']);
    $invoice = Document::factory()->for($user)->invoice()->create(['tax_rate' => 10, 'discount' => 0]);
    DocumentItem::factory()->for($invoice)->billing(2, 150)->create(['description' => 'Consulting']);

    $response = $this->actingAs($user)->get(route('documents.print', $invoice));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('documents/print')
        ->where('document.number', $invoice->number)
        ->where('document.total_cents', 33000)
        ->has('document.items', 1)
        ->where('issuer.name', 'Jane Freelancer')
        ->where('issuer.email', $user->email)
    );
});

test('a document of another user cannot be printed', function (): void {
    $document = Document::factory()->invoice()->create();

    $this
        ->actingAs(User::factory()->create())
        ->get(route('documents.print', $document))
        ->assertForbidden();
});

test('guests are redirected to the login page', function (): void {
    $document = Document::factory()->invoice()->create();

    $this->get(route('documents.print', $document))->assertRedirect(route('login'));
});
