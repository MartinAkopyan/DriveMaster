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

class ConfirmLessonTest extends TestCase
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
    public function instructor_can_confirm_lesson(): void
    {
        $lesson = Lesson::factory()->create([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LessonStatus::PLANNED
        ]);

        $query = "mutation {
                    confirmLesson(lesson_id: {$lesson->id}) {
                        id
                        status
                    }
                }";

        $response = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/graphql", ['query' => $query]);

        $response->assertOk();
        $response->assertJsonPath('data.confirmLesson.status', 'CONFIRMED');
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => LessonStatus::CONFIRMED
        ]);
    }

    /** @test */
    public function instructor_cannot_confirm_other_lessons(): void
    {
        $otherInstructor = User::factory()->create([
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => true
        ]);

        $lesson = Lesson::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'student_id' => $this->student->id,
            'status' => LessonStatus::PLANNED
        ]);

        $query = "mutation {
                    confirmLesson(lesson_id: {$lesson->id}) {
                        id
                        status
                    }
                }";

        $response = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/graphql", ['query' => $query]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'You can only confirm your own lessons');
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => LessonStatus::PLANNED
        ]);
    }
}
