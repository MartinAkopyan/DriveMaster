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

    /** @test */
    public function cancel_expired_lessons_job_continues_on_individual_failure(): void
    {
        Event::fake();

        $instructor = User::factory()->instructor()->approved()->create();
        $student1 = User::factory()->student()->create();
        $student2 = User::factory()->student()->create();

        $lesson1 = Lesson::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student1->id,
            'status' => LessonStatus::PLANNED,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'created_at' => Carbon::now()->subHours(30),
        ]);

        $lesson2 = Lesson::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student2->id,
            'status' => LessonStatus::PLANNED,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'created_at' => Carbon::now()->subHours(30),
        ]);

        $lessonService = Mockery::mock(LessonBookingService::class);
        $lessonService->shouldReceive('getExpiredPendingLessons')
            ->once()
            ->andReturn(collect([$lesson1, $lesson2]));

        $lessonService->shouldReceive('cancelLessonAutomatically')
            ->with(Mockery::on(fn($l) => $l->id === $lesson1->id), Mockery::any())
            ->andThrow(new \Exception('Database error'));

        $lessonService->shouldReceive('cancelLessonAutomatically')
            ->with(Mockery::on(fn($l) => $l->id === $lesson2->id), Mockery::any())
            ->andReturnUsing(function ($lesson, $reason) {
                $lesson->status = LessonStatus::CANCELLED;
                $lesson->cancel_reason = $reason;
                $lesson->save();
                return $lesson;
            });

        Log::shouldReceive('error')->once()
            ->with('Failed to auto-cancel lesson', Mockery::on(function ($context) use ($lesson1) {
                return $context['lesson_id'] === $lesson1->id;
            }));

        Log::shouldReceive('info')->once();

        $job = new CancelExpiredPendingLessonsJob();

        $job->handle($lessonService);

        $lesson2->refresh();
        $this->assertEquals(LessonStatus::CANCELLED, $lesson2->status);
    }

    /** @test */
    public function cancel_expired_lessons_job_logs_failure(): void
    {
        $logCalls = [];

        Log::shouldReceive('error')
            ->once()
            ->andReturnUsing(function ($message, $context) use (&$logCalls) {
                $logCalls[] = ['message' => $message, 'context' => $context];
            });

        $exception = new \Exception('Database connection lost');
        $job = new CancelExpiredPendingLessonsJob();

        // Act
        $job->failed($exception);

        // Assert
        $this->assertCount(1, $logCalls);
        $this->assertEquals('CancelExpiredPendingLessons job failed', $logCalls[0]['message']);
        $this->assertEquals($exception->getMessage(), $logCalls[0]['context']['error']);
        $this->assertArrayHasKey('trace', $logCalls[0]['context']);
        $this->assertArrayHasKey('file', $logCalls[0]['context']);
        $this->assertArrayHasKey('line', $logCalls[0]['context']);
    }

}
