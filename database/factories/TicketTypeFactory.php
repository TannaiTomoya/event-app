<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['一般', '学生', 'ペア']),
            'price' => fake()->randomElement([0, 1000, 2000, 3000, 5000]),
            'capacity' => fake()->numberBetween(10, 50),
        ];
    }
}
