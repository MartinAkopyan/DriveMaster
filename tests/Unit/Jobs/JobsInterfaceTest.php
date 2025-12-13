<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CancelExpiredPendingLessonsJob;
use App\Jobs\GenerateAdminReportJob;
use App\Jobs\SendLessonRemindersJob;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class JobsInterfaceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function jobs_implement_should_queue_interface(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new CancelExpiredPendingLessonsJob()
        );

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new SendLessonRemindersJob()
        );

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new GenerateAdminReportJob(1, 'weekly', '2024-12-01', '2024-12-07')
        );
    }

    /** @test */
    public function jobs_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        CancelExpiredPendingLessonsJob::dispatch();
        SendLessonRemindersJob::dispatch();
        GenerateAdminReportJob::dispatch(1, 'weekly', '2024-12-01', '2024-12-07');

        Queue::assertPushed(CancelExpiredPendingLessonsJob::class);
        Queue::assertPushed(SendLessonRemindersJob::class);
        Queue::assertPushed(GenerateAdminReportJob::class);
    }
}
