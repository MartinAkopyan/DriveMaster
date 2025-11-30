<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => $this->faker->phoneNumber(),
            'bio' => $this->faker->sentence(),
            'experience_years' => $this->faker->numberBetween(0, 20),
            'car_model' => $this->faker->randomElement([
                'Toyota Corolla',
                'Honda Civic',
                'Ford Focus',
                'Mazda 3',
                'BMW 3 Series',
            ]),
        ];
    }
}
