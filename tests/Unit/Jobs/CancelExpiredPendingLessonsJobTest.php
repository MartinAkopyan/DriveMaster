<?php

namespace Tests\Unit\Jobs;

use App\Enums\LessonStatus;
use App\Events\LessonCancelledBySystem;
use App\Jobs\CancelExpiredPendingLessonsJob;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LessonBookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CancelExpiredPendingLessonsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function cancel_expired_lessons_job_has_correct_configuration(): void
    {
        $job = new CancelExpiredPendingLessonsJob();

        $this->assertEquals(1, $job->tries, 'Job should have 1 try');
        $this->assertEquals(120, $job->timeout, 'Job should timeout after 120 seconds');
    }

    /** @test */
    public function cancel_expired_lessons_job_cancels_old_pending_lessons(): void
    {
        Event::fake([LessonCancelledBySystem::class]);

        $instructor = User::factory()->instructor()->approved()->create();
        $student = User::factory()->student()->create();

        $oldLesson = Lesson::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => LessonStatus::PLANNED,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'created_at' => Carbon::now()->subHours(26),
        ]);

        $recentLesson = Lesson::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => LessonStatus::PLANNED,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'created_at' => Carbon::now()->subHours(2),
        ]);

        Log::shouldReceive('info')->once();

        $job = new CancelExpiredPendingLessonsJob();
        $service = app(LessonBookingService::class);

        $job->handle($service);

        $oldLesson->refresh();
        $recentLesson->refresh();

        $this->assertEquals(LessonStatus::CANCELLED, $oldLesson->status);
        $this->assertEquals(LessonStatus::PLANNED, $recentLesson->status);

        Event::assertDispatched(LessonCancelledBySystem::class);
    }
}
