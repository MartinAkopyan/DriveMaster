<?php

namespace Tests\Unit\Repositories;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use App\Models\User;
use App\Repositories\LessonRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LessonRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected LessonRepository $repo;
    protected User $instructor;
    protected User $student;
    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new LessonRepository();
        $this->instructor = User::factory()
            ->instructor()
            ->approved()
            ->create();
        $this->student = User::factory()
            ->student()
            ->create();
        $this->date = Carbon::tomorrow()->format('Y-m-d');
    }

    /** @test */
    public function detects_conflict_when_slot_already_booked(): void
    {
        Lesson::factory()->create([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'start_time' => Carbon::parse($this->date)->setTime(8, 0),
            'end_time' => Carbon::parse($this->date)->setTime(10, 0),
            'status' => LessonStatus::CONFIRMED,
        ]);

        $hasConflict = $this->repo->hasInstructorConflict(
            $this->instructor->id,
            Carbon::parse($this->date)->setTime(8, 0),
            Carbon::parse($this->date)->setTime(10, 0)
        );

        $this->assertTrue($hasConflict);
    }

    /** @test */
    public function ignores_cancelled_lessons(): void
    {
        Lesson::factory()->create([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'start_time' => Carbon::parse($this->date)->setTime(8, 0),
            'end_time' => Carbon::parse($this->date)->setTime(10, 0),
            'status' => LessonStatus::CANCELLED
        ]);

        $hasConflict = $this->repo->hasInstructorConflict(
            $this->instructor->id,
            Carbon::parse($this->date)->setTime(8, 0),
            Carbon::parse($this->date)->setTime(10, 0)
        );

        $this->assertFalse($hasConflict);
    }

    /** @test */
    public function checks_only_specific_instructors(): void
    {
        $otherInstructor = User::factory()->instructor()->approved()->create();

        Lesson::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'start_time' => Carbon::parse($this->date)->setTime(8, 0),
            'end_time' => Carbon::parse($this->date)->setTime(10, 0),
            'status' => LessonStatus::CONFIRMED,
        ]);

        $hasConflict = $this->repo->hasInstructorConflict(
            $this->instructor->id,
            Carbon::parse($this->date)->setTime(8, 0),
            Carbon::parse($this->date)->setTime(10, 0)
        );

        $this->assertFalse($hasConflict);
    }

    /** @test */
    public function caches_instructor_schedule(): void
    {
        Cache::flush();

        $dateFrom = Carbon::parse($this->date);
        $dateTo = Carbon::parse($this->date);

        for ($slot = 1; $slot <= 3; $slot++) {
            Lesson::factory()->create([
                'instructor_id' => $this->instructor->id,
                'student_id' => $this->student->id,
                'start_time' => Carbon::parse($this->date)->setTime(6 + $slot * 2, 0),
                'end_time' => Carbon::parse($this->date)->setTime(8 + $slot * 2, 0),
            ]);
        }

        $lessons1 = $this->repo->getInstructorSchedule($this->instructor->id, $dateFrom, $dateTo);

        $lessons2 = $this->repo->getInstructorSchedule($this->instructor->id, $dateFrom->copy(), $dateTo->copy());

        $this->assertCount(3, $lessons1);
        $this->assertCount(3, $lessons2);

        $cacheKey = sprintf(
            'instructor_schedule:%d:%s:%s:all',
            $this->instructor->id,
            $dateFrom->format('Y-m-d'),
            $dateTo->format('Y-m-d')
        );

        $this->assertTrue(
            Cache::tags(['lessons', "lessons:instructor:{$this->instructor->id}"])->has($cacheKey),
            'Data should be cached'
        );

        $this->assertEquals($lessons1->pluck('id'), $lessons2->pluck('id'));
    }

    /** @test */
    public function invalidates_cache_after_create(): void
    {
        Cache::flush();

        $dateFrom = Carbon::parse($this->date);
        $dateTo = Carbon::parse($this->date);

        $this->repo->getInstructorSchedule($this->instructor->id, $dateFrom, $dateTo);

        $cacheKey = sprintf(
            'instructor_schedule:%d:%s:%s:all',
            $this->instructor->id,
            $dateFrom->format('Y-m-d'),
            $dateTo->format('Y-m-d')
        );

        $this->assertTrue(
            Cache::tags(['lessons', "lessons:instructor:{$this->instructor->id}"])->has($cacheKey),
            'Cache should exist before create'
        );

        $this->repo->createLesson([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'start_time' => Carbon::parse($this->date)->setTime(10, 0),
            'end_time' => Carbon::parse($this->date)->setTime(12, 0),
            'status' => LessonStatus::PLANNED,
        ]);

        $this->assertFalse(
            Cache::tags(['lessons', "lessons:instructor:{$this->instructor->id}"])->has($cacheKey),
            'Cache should be invalidated after create'
        );
    }

    /** @test */
    public function eager_loads_relationships(): void
    {
        $students = User::factory()->student()->count(3)->create();

        foreach ($students as $index => $student) {
            Lesson::factory()->create([
                'instructor_id' => $this->instructor->id,
                'student_id' => $student->id,
                'start_time' => Carbon::parse($this->date)->setTime(8 + $index * 2, 0),
                'end_time' => Carbon::parse($this->date)->setTime(10 + $index * 2, 0),
            ]);
        }

        $lessons = $this->repo->getInstructorSchedule(
            $this->instructor->id,
            Carbon::parse($this->date),
            Carbon::parse($this->date)
        );

        $this->assertCount(3, $lessons);

        foreach ($lessons as $lesson) {
            $this->assertNotNull($lesson->instructor);
            $this->assertNotNull($lesson->student);

            $this->assertTrue($lesson->relationLoaded('instructor'));
            $this->assertTrue($lesson->relationLoaded('student'));
        }
    }
}
