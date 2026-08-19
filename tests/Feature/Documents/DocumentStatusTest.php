<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;

test('the status of an invoice can be changed', function (): void {
    $user = User::factory()->create();
    $invoice = Document::factory()->for($user)->invoice()->sent()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('documents.index'))
        ->patch(route('documents.status.update', $invoice), ['status' => 'paid']);

    $response->assertSessionHasNoErrors()->assertRedirect(route('documents.index'));

    expect($invoice->refresh()->status)->toBe(DocumentStatus::Paid);
});

test('the status of a quotation can be changed', function (): void {
    $user = User::factory()->create();
    $quotation = Document::factory()->for($user)->quotation()->sent()->create();

    $this
        ->actingAs($user)
        ->from(route('documents.index', ['type' => 'quotation']))
        ->patch(route('documents.status.update', $quotation), ['status' => 'declined'])
        ->assertSessionHasNoErrors();

    expect($quotation->refresh()->status)->toBe(DocumentStatus::Declined);
});

test('a status of the other document type is rejected', function (string $type, string $status): void {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->{$type}()->sent()->create();

    $this
        ->actingAs($user)
        ->patch(route('documents.status.update', $document), ['status' => $status])
        ->assertSessionHasErrors('status');

    expect($document->refresh()->status)->toBe(DocumentStatus::Sent);
})->with([
    'an invoice cannot be accepted' => ['invoice', 'accepted'],
    'an invoice cannot be declined' => ['invoice', 'declined'],
    'a quotation cannot be paid' => ['quotation', 'paid'],
]);

test('the status of a document cannot be changed by another user', function (): void {
    $document = Document::factory()->invoice()->sent()->create();

    $this
        ->actingAs(User::factory()->create())
        ->patch(route('documents.status.update', $document), ['status' => 'paid'])
        ->assertForbidden();

    expect($document->refresh()->status)->toBe(DocumentStatus::Sent);
});
