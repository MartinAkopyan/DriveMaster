<?php

namespace Tests\Feature;

use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LessonBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $instructor;

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
    }

    /** @test */
    public function student_can_book_lesson_test(): void
    {
        $date = now()->addDays(5)->format('Y-m-d');
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', [
                'query' => "mutation {
                    bookLesson(
                        instructor_id: {$this->instructor->id},
                        date: \"{$date}\",
                        slot: 1
                    ) {
                        id,
                        status
                    }
                }"
            ]);


        $response->assertOk();
        $response->assertJsonPath('data.bookLesson.status', 'PLANNED');

        $this->assertDatabaseHas('lessons', [
            'student_id' => $this->student->id,
            'instructor_id' => $this->instructor->id,
            'status' => LessonStatus::PLANNED
        ]);
    }

    /** @test */
    public function instructor_cannot_book_lesson(): void
    {
        $date = now()->addDays(5)->format('Y-m-d');

        $query = "
        mutation {
            bookLesson(
                instructor_id: {$this->instructor->id},
                date: \"{$date}\",
                slot: 1
            ) {
                id
            }
        }
    ";

        $response = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        \Log::info('after mutation');

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Only students can book lessons');

        \Log::info($response->json());
        \Log::info('after response');

        $this->assertDatabaseCount('lessons', 0);
    }
}
