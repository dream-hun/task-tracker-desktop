<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('documents.index'));

    $response->assertRedirect(route('login'));
});

test('the list shows invoices unless another type is asked for', function (): void {
    $user = User::factory()->create();
    Document::factory()->for($user)->invoice()->create(['client_name' => 'Acme Industries']);
    Document::factory()->for($user)->quotation()->create(['client_name' => 'Globex Corporation']);

    $response = $this->actingAs($user)->get(route('documents.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('documents/index')
        ->where('type', 'invoice')
        ->has('documents.data', 1)
        ->where('documents.data.0.client_name', 'Acme Industries')
    );
});

test('quotations can be listed', function (): void {
    $user = User::factory()->create();
    Document::factory()->for($user)->invoice()->create(['client_name' => 'Acme Industries']);
    Document::factory()->for($user)->quotation()->create(['client_name' => 'Globex Corporation']);

    $response = $this->actingAs($user)->get(route('documents.index', ['type' => 'quotation']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('type', 'quotation')
        ->has('documents.data', 1)
        ->where('documents.data.0.client_name', 'Globex Corporation')
        ->where('statuses', ['draft', 'sent', 'accepted', 'declined', 'cancelled'])
    );
});

test('users only see their own documents', function (): void {
    $user = User::factory()->create();
    Document::factory()->for($user)->invoice()->create(['client_name' => 'Acme Industries']);
    Document::factory()->invoice()->create(['client_name' => 'Someone Else']);

    $response = $this->actingAs($user)->get(route('documents.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('documents.data', 1)
        ->where('documents.data.0.client_name', 'Acme Industries')
    );
});

test('documents can be filtered by status', function (): void {
    $user = User::factory()->create();
    Document::factory()->for($user)->invoice()->draft()->create(['client_name' => 'Still Drafting']);
    Document::factory()->for($user)->invoice()->paid()->create(['client_name' => 'Already Paid']);

    $response = $this->actingAs($user)->get(route('documents.index', ['status' => 'paid']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('documents.data', 1)
        ->where('documents.data.0.client_name', 'Already Paid')
        ->where('filters.status', 'paid')
    );
});

test('a status that does not apply to the listed type is ignored', function (): void {
    $user = User::factory()->create();
    Document::factory()->for($user)->quotation()->count(2)->create();

    $response = $this->actingAs($user)->get(route('documents.index', ['type' => 'quotation', 'status' => 'paid']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('documents.data', 2)
        ->where('filters.status', null)
    );
});

test('documents can be searched by number, client name and client email', function (): void {
    $user = User::factory()->create();
    Document::factory()->for($user)->invoice()->create(['number' => 'INV-2026-0042', 'client_name' => 'Acme', 'client_email' => 'ap@acme.test']);
    Document::factory()->for($user)->invoice()->create(['number' => 'INV-2026-0043', 'client_name' => 'Wayne Enterprises', 'client_email' => 'billing@acme-supplies.test']);
    Document::factory()->for($user)->invoice()->create(['number' => 'INV-2026-0044', 'client_name' => 'Stark Industries', 'client_email' => 'ap@stark.test']);

    $response = $this->actingAs($user)->get(route('documents.index', ['search' => 'acme']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('documents.data', 2)
        ->where('filters.search', 'acme')
    );
});

test('the list summarizes the documents of the type it shows', function (): void {
    $user = User::factory()->create();
    $zeroRated = ['tax_rate' => 0, 'discount' => 0];

    $draft = Document::factory()->for($user)->invoice()->draft()->currency('USD')->create($zeroRated);
    DocumentItem::factory()->for($draft)->billing(1, 100)->create();

    $open = Document::factory()->for($user)->invoice()->sent()->currency('USD')->create([...$zeroRated, 'due_date' => now()->addWeek()]);
    DocumentItem::factory()->for($open)->billing(2, 250)->create();

    $overdue = Document::factory()->for($user)->invoice()->overdue()->currency('USD')->create($zeroRated);
    DocumentItem::factory()->for($overdue)->billing(1, 75)->create();

    $paid = Document::factory()->for($user)->invoice()->paid()->currency('USD')->create($zeroRated);
    DocumentItem::factory()->for($paid)->billing(3, 30)->create();

    Document::factory()->for($user)->quotation()->create();

    $response = $this->actingAs($user)->get(route('documents.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('stats.total', 4)
        ->where('stats.drafts', 1)
        ->where('stats.open', 2)
        ->where('stats.overdue', 1)
        ->where('stats.settled', 1)
        ->where('stats.open_cents', 57500)
        ->where('stats.settled_cents', 9000)
        ->where('stats.currency', 'USD')
    );
});

test('the summary reports no currency when the documents mix them', function (): void {
    $user = User::factory()->create();
    Document::factory()->for($user)->invoice()->currency('USD')->create();
    Document::factory()->for($user)->invoice()->currency('EUR')->create();

    $response = $this->actingAs($user)->get(route('documents.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('stats.currency', null)
    );
});

test('the most recently issued documents are listed first', function (): void {
    $user = User::factory()->create();
    Document::factory()->for($user)->invoice()->create(['number' => 'INV-2026-0001', 'issue_date' => '2026-01-05', 'due_date' => null]);
    Document::factory()->for($user)->invoice()->create(['number' => 'INV-2026-0009', 'issue_date' => '2026-03-01', 'due_date' => null]);

    $response = $this->actingAs($user)->get(route('documents.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('documents.data.0.number', 'INV-2026-0009')
        ->where('documents.data.1.number', 'INV-2026-0001')
    );
});

test('the document list is paginated', function (): void {
    $user = User::factory()->create();
    Document::factory()->for($user)->invoice()->count(12)->create();

    $response = $this->actingAs($user)->get(route('documents.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('documents.data', 10)
        ->where('documents.total', 12)
        ->where('documents.last_page', 2)
    );
});

test('each listed document carries its lines and totals', function (): void {
    $user = User::factory()->create();
    $invoice = Document::factory()->for($user)->invoice()->create(['tax_rate' => 10, 'discount' => 5]);
    DocumentItem::factory()->for($invoice)->billing(2, 50)->create();

    $response = $this->actingAs($user)->get(route('documents.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('documents.data.0.items', 1)
        ->where('documents.data.0.subtotal_cents', 10000)
        ->where('documents.data.0.tax_cents', 950)
        ->where('documents.data.0.total_cents', 10450)
    );
});
