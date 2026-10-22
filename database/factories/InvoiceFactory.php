<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
        $status = fake()->randomElement($statuses);
        $issueDate = fake()->dateTimeBetween('-30 days', 'now');
        $dueDate = fake()->dateTimeBetween('+7 days', '+30 days');

        return [
            'agency_id' => Agency::factory(),
            'client_id' => Client::factory(),
            'invoice_number' => 'INV-'.strtoupper(fake()->bothify('##??##')),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => fake()->randomFloat(2, 100, 5000),
            'tax_rate' => fake()->randomFloat(2, 0, 20),
            'tax_amount' => fake()->randomFloat(2, 0, 500),
            'total' => fake()->randomFloat(2, 100, 6000),
            'currency' => 'USD',
            'status' => $status,
            'notes' => fake()->sentence(),
            'paid_at' => $status === 'paid' ? fake()->dateTimeBetween('-7 days', 'now') : null,
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => 'overdue',
            'due_date' => now()->subDays(7),
        ]);
    }
}
