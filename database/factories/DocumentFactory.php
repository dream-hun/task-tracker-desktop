<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->boolean() ? DocumentType::Invoice : DocumentType::Quotation;
        $issuedAt = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'status' => fake()->randomElement($type->statuses()),
            'number' => $this->number($type),
            'client_name' => fake()->company(),
            'client_email' => fake()->companyEmail(),
            'client_address' => fake()->address(),
            'issue_date' => $issuedAt,
            'due_date' => fake()->dateTimeBetween($issuedAt, '+1 month'),
            'currency' => fake()->randomElement(Document::CURRENCIES),
            'tax_rate' => fake()->randomElement([0, 7.5, 18, 20]),
            'discount' => 0,
            'notes' => fake()->boolean(40) ? fake()->sentence() : null,
        ];
    }

    /**
     * Indicate that the document is an invoice.
     */
    public function invoice(): static
    {
        return $this->ofType(DocumentType::Invoice);
    }

    /**
     * Indicate that the document is a quotation.
     */
    public function quotation(): static
    {
        return $this->ofType(DocumentType::Quotation);
    }

    /**
     * Indicate that the document has not been sent yet.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Draft,
        ]);
    }

    /**
     * Indicate that the document is waiting on the client.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Sent,
        ]);
    }

    /**
     * Indicate that the invoice has been paid.
     */
    public function paid(): static
    {
        return $this->invoice()->state(fn (array $attributes) => [
            'status' => DocumentStatus::Paid,
        ]);
    }

    /**
     * Indicate that the quotation has been accepted.
     */
    public function accepted(): static
    {
        return $this->quotation()->state(fn (array $attributes) => [
            'status' => DocumentStatus::Accepted,
        ]);
    }

    /**
     * Indicate that the document is still open and past its due date.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Sent,
            'issue_date' => now()->subMonth(),
            'due_date' => now()->subWeek(),
        ]);
    }

    /**
     * Indicate that the document is billed in the given currency.
     */
    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }

    /**
     * Indicate the type of the document, keeping its number and status in step.
     */
    protected function ofType(DocumentType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'number' => $this->number($type),
            'status' => fake()->randomElement($type->statuses()),
        ]);
    }

    /**
     * Build a unique document number for the given type.
     */
    protected function number(DocumentType $type): string
    {
        return sprintf('%s-%d-%04d', $type->prefix(), today()->year, fake()->unique()->numberBetween(1, 9999));
    }
}
