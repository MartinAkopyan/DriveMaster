<?php

namespace Tests\Feature\GraphQL\Queries;

use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AvailableSlotsTest extends TestCase
{
    use RefreshDatabase;
    private User $student;
    private User $instructor;
    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->instructor = User::factory()->create([
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => true
        ]);

        Profile::factory()->create(['user_id' => $this->instructor->id]);

        $this->date = now()->addDays(5)->format('Y-m-d');
    }

    /** @test */
    public function show_all_available_slots_when_no_lessons_booked(): void
    {
        $query = $this->buildQuery($this->instructor->id, $this->date);

        $response = $this->actingAs($this->student)
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();
        $this->assertCount(6,$response->json('data.availableSlots'));

        $response->assertJsonStructure([
            'data' => [
                'availableSlots' => [
                    '*' => ['start_time', 'end_time']
                ]
            ]
        ]);

        $firstSlot = $response->json('data.availableSlots.0');
        $this->assertStringContainsString('08:00:00', $firstSlot['start_time']);
        $this->assertStringContainsString('10:00:00', $firstSlot['end_time']);
    }

    /** @test */
    public function does_not_show_booked_slots(): void
    {
        Lesson::factory()->create([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->instructor->id,
            'start_time' => now()->addDays(5)->setTime(8, 0),
            'end_time' => now()->addDays(5)->setTime(10, 0),
            'status' => LessonStatus::PLANNED
        ]);

        Lesson::factory()->create([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->instructor->id,
            'start_time' => now()->addDays(5)->setTime(12, 0),
            'end_time' => now()->addDays(5)->setTime(14, 0),
            'status' => LessonStatus::CONFIRMED
        ]);

        $query = $this->buildQuery($this->instructor->id, $this->date);

        $response = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('graphql', ['query' => $query]);

        $response->assertOk();

        $this->assertCount(4, $response->json('data.availableSlots'));

        $slots = $response->json('data.availableSlots');

        foreach ($slots as $slot) {
            $this->assertStringNotContainsString('08:00:00', $slot['start_time']);
            $this->assertStringNotContainsString('12:00:00', $slot['start_time']);
        }
    }

    /** @test */
    public function shows_slots_if_lesson_is_cancelled(): void
    {
        Lesson::factory()->create([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->instructor->id,
            'start_time' => now()->addDays(5)->setTime(8, 0),
            'end_time' => now()->addDays(5)->setTime(10, 0),
            'status' => LessonStatus::CANCELLED
        ]);

        $query = $this->buildQuery($this->instructor->id, $this->date);

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();

        $this->assertCount(6, $response->json('data.availableSlots'));

    }

    private function buildQuery(int $instructorId, string $date): string
    {
        return "
            query {
                availableSlots(
                    instructor_id: {$instructorId},
                    date: \"{$date}\"
                ) {
                    start_time
                    end_time
                }
            }
        ";
    }
}
