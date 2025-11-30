<?php

namespace Database\Factories;

use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'instructor_id' => User::factory()->create([
                'role' => UserRole::INSTRUCTOR,
                'is_active' => true
            ]),
            'student_id' => User::factory()->create([
                'role' => UserRole::STUDENT,
            ]),
            'start_time' => now()->addDays(3)->setHour(8),
            'end_time' => now()->addDays(3)->setHour(10)
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => LessonStatus::CONFIRMED]);
    }
}
