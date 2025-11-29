<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateWeeklyReportsForAdminsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $admins = User::where('role', UserRole::ADMIN)->get();

        $dateFrom = now()->subWeek()->startOfWeek();
        $dateTo = now()->subWeek()->endOfWeek();

        $dispatchedCount = 0;

        foreach ($admins as $admin) {
            GenerateAdminReportJob::dispatch(
                adminId: $admin->id,
                reportType: 'weekly',
                dateFrom: $dateFrom,
                dateTo: $dateTo,
            )->onQueue('default');

            $dispatchedCount++;
        }

        Log::info('Weekly reports dispatched', [
            'admins_count' => $dispatchedCount,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateWeeklyReportsForAdmins failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
