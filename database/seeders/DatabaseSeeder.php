<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Task::factory()->for($user)->count(8)->create();
        Task::factory()->for($user)->overdue()->highPriority()->count(2)->create();

        Document::factory()
            ->for($user)
            ->invoice()
            ->count(4)
            ->currency(Document::DEFAULT_CURRENCY)
            ->has(DocumentItem::factory()->count(3), 'items')
            ->create();

        Document::factory()
            ->for($user)
            ->quotation()
            ->count(3)
            ->currency(Document::DEFAULT_CURRENCY)
            ->has(DocumentItem::factory()->count(2), 'items')
            ->create();
    }
}
