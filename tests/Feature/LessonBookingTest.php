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
    private String $date;

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
    public function student_can_book_lesson_test(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', [
                'query' => "mutation {
                    bookLesson(
                        instructor_id: {$this->instructor->id},
                        date: \"{$this->date}\",
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
                date: \"{$this->date}\",
                slot: 1
            ) {
                id
            }
        }";

        $response = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Only students can book lessons');

        $this->assertDatabaseCount('lessons', 0);
    }

    /** @test */
    public function cannot_book_with_unapproved_instructor(): void
    {
        $unApprovedInstructor = User::factory()->create([
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => false
        ]);

        $date = now()->addDays(5)->format('Y-m-d');

        $query = "
            mutation{
                bookLesson(
                    instructor_id: {$unApprovedInstructor->id},
                    date: \"{$this->date}\",
                    slot: 1
                ) {
                    id
                }
            }
        ";

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        \Log::info($response->json());

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Instructor not found or not approved');
        $this->assertDatabaseCount('lessons', 0);
    }

    /** @test */
    public function cannot_book_invalid_slot(): void
    {
        $query = "
            mutation {
                bookLesson(
                    instructor_id: {$this->instructor->id},
                    date: \"{$this->date}\",
                    slot: 10
                ) {
                    id
                }
            }";

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Invalid slot number. Must be between 1 and 6');
        $this->assertDatabaseCount('lessons', 0);
    }
}
