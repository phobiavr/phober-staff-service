<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'instance_id' => $this->faker->numberBetween(1, 10),
            'serviced_by' => Employee::factory(),
            'invoice_id' => Invoice::factory(),
            'time' => $this->faker->randomElement([15, 30, 60]),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'status' => SessionStatusEnum::QUEUE->value,
            'started_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => SessionStatusEnum::ACTIVE->value,
            'started_at' => now(),
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn () => [
            'status' => SessionStatusEnum::FINISHED->value,
            'started_at' => now()->subHour(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => ['status' => SessionStatusEnum::CANCELED->value]);
    }
}
