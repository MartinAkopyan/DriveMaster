<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\AdminReportGeneratedNotification;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateAdminReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $tries = 3;
    public $timeout = 120;
    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $adminId,
        public string $reportType,
        public string $dateFrom,
        public string $dateTo

    ){}

    /**
     * Execute the job.
     * @throws \Exception
     */
    public function handle(ReportService $reportService): void
    {
        $admin = User::findOrFail($this->adminId);
        $dateFrom = Carbon::parse($this->dateFrom);
        $dateTo = Carbon::parse($this->dateTo);

        $data = $this->gatherReportData($dateFrom, $dateTo, $reportService, $admin);

        $pdf = Pdf::loadView('reports.admin', [
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'reportType' => $this->reportType,
        ]);

        $fileName = "report_{$this->reportType}_{$dateFrom->format('Y-m-d')}_{$dateTo->format('Y-m-d')}.pdf";
        $path = "reports/{$fileName}";

        Storage::put($path, $pdf->output());

        $admin->notify(new AdminReportGeneratedNotification($path, $fileName));

        Log::info('Admin report generated', [
            'admin_id' => $admin->id,
            'report_type' => $this->reportType,
            'filename' => $fileName
        ]);
    }

    /**
     * @throws \Exception
     */
    private function gatherReportData(Carbon $dateFrom, Carbon $dateTo, ReportService $reportService, User $admin): array
    {
        $lessonStats = $reportService->getLessonsStats($dateFrom, $dateTo, $admin);

        return [
            'total_lessons' => $lessonStats['total'],
            'confirmed_lessons' => $lessonStats['confirmed'],
            'cancelled_lessons' => $lessonStats['cancelled'],
            'completed_lessons' => $lessonStats['completed'],
            'top_instructors' => $reportService->getTopInstructors($dateFrom, $dateTo, $admin),
            'instructor_stats' => $reportService->getInstructorsStats($admin)
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateAdminReport job failed', [
            'admin_id' => $this->adminId,
            'error' => $exception->getMessage(),
        ]);
    }
}
