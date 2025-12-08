<?php

namespace Tests\Feature\GraphQL\Mutations;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CancelLessonTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $instructor;
    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = User::factory()
            ->student()
            ->create();

        $this->instructor = User::factory()
            ->instructor()
            ->approved()
            ->create();

        $this->date = now()->addDays(5)->format('Y-m-d');
    }

    /** @test */
    public function student_can_cancel_lesson_12_hours_before(): void
    {
        $lesson = Lesson::factory()->create([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LessonStatus::PLANNED,
            'start_time' => now()->addHours(24),
            'end_time' => now()->addHours(26),
        ]);

        $query = $this->buildMutation($lesson, 'Personal reasons');

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/graphql", ['query' => $query]);

        $response->assertOk();
        $response->assertJsonPath('data.cancelLesson.status', 'cancelled');
        $response->assertJsonPath('data.cancelLesson.cancel_reason', 'Personal reasons');
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => LessonStatus::CANCELLED
        ]);
    }

    /** @test */
    public function student_cannot_cancel_less_than_12_hours_before(): void
    {
        $lesson = Lesson::factory()->create([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LessonStatus::PLANNED,
            'start_time' => now()->addHours(6),
            'end_time' => now()->addHours(8),
        ]);

        $query = $this->buildMutation($lesson);

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Students must cancel lesson at least 12 hours in advance');
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => LessonStatus::PLANNED
        ]);
    }

    /** @test */
    public function instructor_can_cancel_anytime(): void
    {
        $lesson = Lesson::factory()->create([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LessonStatus::PLANNED,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(4),
        ]);

        $query = $this->buildMutation($lesson);

        $response = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();

        $response->assertJsonPath('data.cancelLesson.status', 'cancelled');
        $response->assertJsonPath('data.cancelLesson.cancel_reason', 'Emergency');
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => LessonStatus::CANCELLED
        ]);
    }

    private function buildMutation(Lesson $lesson, string $reason = 'Emergency'): string
    {
        return "mutation {
                    cancelLesson(lesson_id: {$lesson->id}, reason: \"{$reason}\") {
                        id
                        status
                        cancel_reason
                    }
                }";
    }
}
