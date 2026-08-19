<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentItem>
 */
final class DocumentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'description' => fake()->sentence(3),
            'quantity' => fake()->randomElement([1, 2, 3, 7.5, 12]),
            'unit_price' => fake()->randomFloat(2, 25, 2500),
            'position' => 0,
        ];
    }

    /**
     * Indicate the price and quantity the line bills.
     */
    public function billing(float|int|string $quantity, float|int|string $unitPrice): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ]);
    }
}
