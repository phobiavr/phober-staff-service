<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'customer' => 'Quest',
            'status' => InvoiceStatusEnum::QUEUE->value,
            'payment_method' => null,
        ];
    }

    public function payed(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatusEnum::PAYED->value,
            'payment_method' => ['CASH' => 100],
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => ['status' => InvoiceStatusEnum::CANCELED->value]);
    }
}
