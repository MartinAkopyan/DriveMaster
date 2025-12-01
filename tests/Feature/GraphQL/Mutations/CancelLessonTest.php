<?php

namespace Tests\Feature\GraphQL\Mutations;

use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\Profile;
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
        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT
        ]);

        $this->instructor = User::factory()->create([
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => true
        ]);

        Profile::factory()->create(['user_id' => $this->instructor->id]);

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

        $query = "mutation {
                    cancelLesson(lesson_id: {$lesson->id}, reason: \"Personal reasons\") {
                        id
                        status
                        cancel_reason
                    }
                }";

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/graphql", ['query' => $query]);
        \Log::info($response->json());
        $response->assertOk();
        $response->assertJsonPath('data.cancelLesson.status', 'CANCELLED');
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

        $query = "mutation {
                    cancelLesson(lesson_id: {$lesson->id}) {
                        id
                    }
                }";

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

        $query = "mutation {
                    cancelLesson(lesson_id: {$lesson->id}, reason: \"Emergency\") {
                        id
                        status
                        cancel_reason
                    }
                }";

        $response = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();

        $response->assertJsonPath('data.cancelLesson.status', 'CANCELLED');
        $response->assertJsonPath('data.cancelLesson.cancel_reason', 'Emergency');
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => LessonStatus::CANCELLED
        ]);
    }
}
