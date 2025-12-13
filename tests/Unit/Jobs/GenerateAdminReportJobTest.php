<?php

namespace Tests\Unit\Jobs;

use App\Enums\LessonStatus;
use App\Jobs\GenerateAdminReportJob;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\AdminReportGeneratedNotification;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GenerateAdminReportJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function generate_admin_report_job_has_correct_configuration(): void
    {
        $job = new GenerateAdminReportJob(
            adminId: 1,
            reportType: 'weekly',
            dateFrom: '2024-12-01',
            dateTo: '2024-12-07'
        );

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }

    /** @test */
    public function generate_admin_report_job_creates_pdf_file(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $instructor = User::factory()->instructor()->approved()->create();
        $student = User::factory()->student()->create();

        Lesson::factory()->count(5)->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => LessonStatus::CONFIRMED,
            'start_time' => Carbon::now()->subDays(3),
            'end_time' => Carbon::now()->subDays(3)->addHours(2),
        ]);

        $job = new GenerateAdminReportJob(
            adminId: $admin->id,
            reportType: 'weekly',
            dateFrom: Carbon::now()->subWeek()->format('Y-m-d'),
            dateTo: Carbon::now()->format('Y-m-d')
        );

        Log::shouldReceive('info')->once();

        $job->handle(app(ReportService::class));

        $files = Storage::files('reports');
        $this->assertCount(1, $files);
        $this->assertStringContainsString('.pdf', $files[0]);

        Notification::assertSentTo($admin, AdminReportGeneratedNotification::class);
    }


    /** @test */
    public function generate_admin_report_job_handles_different_report_types(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $reportTypes = ['daily', 'weekly', 'monthly', 'custom'];

        foreach ($reportTypes as $type) {
            $job = new GenerateAdminReportJob(
                adminId: $admin->id,
                reportType: $type,
                dateFrom: Carbon::now()->subDays(7)->format('Y-m-d'),
                dateTo: Carbon::now()->format('Y-m-d')
            );

            Log::shouldReceive('info')->once();

            $job->handle(app(ReportService::class));
        }

        $files = Storage::files('reports');
        $this->assertCount(4, $files);
    }

    /** @test */
    public function generate_admin_report_job_logs_failure(): void
    {
        $logCalls = [];

        Log::shouldReceive('error')
            ->once()
            ->andReturnUsing(function ($message, $context) use (&$logCalls) {
                $logCalls[] = ['message' => $message, 'context' => $context];
            });

        $exception = new \Exception('PDF generation failed');
        $job = new GenerateAdminReportJob(
            adminId: 1,
            reportType: 'weekly',
            dateFrom: '2024-12-01',
            dateTo: '2024-12-07'
        );

        $job->failed($exception);

        $this->assertCount(1, $logCalls);
        $this->assertEquals('GenerateAdminReport job failed', $logCalls[0]['message']);
        $this->assertEquals(1, $logCalls[0]['context']['admin_id']);
        $this->assertEquals($exception->getMessage(), $logCalls[0]['context']['error']);
    }
}
