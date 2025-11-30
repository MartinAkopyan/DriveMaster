<?php

namespace App\Console;

use App\Jobs\CancelExpiredPendingLessonsJob;
use App\Jobs\GenerateWeeklyReportsForAdminsJob;
use App\Jobs\SendLessonRemindersJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new SendLessonRemindersJob())
            ->dailyAt('08:00')
            ->onSuccess(fn() => Log::info('Lesson reminders sent successfully'))
            ->onFailure(fn() => Log::error('Failed to sent lesson reminders reminders'));

        $schedule->job(new CancelExpiredPendingLessonsJob())
            ->hourly()
            ->withoutOverlapping()
            ->onSuccess(fn() => Log::info('Expired lessons check completed'))
            ->onFailure(fn() => Log::error('Expired lessons check failed'));

        $schedule->job(new GenerateWeeklyReportsForAdminsJob())
            ->weeklyOn(1, '08:00')
            ->onFailure(fn() => Log::error('Failed to generate weekly reports'));

        $schedule->call(function(){
            $deleted = DB::table('notifications')
                ->where('created_at', '<', now()->subDays(7))
                ->delete();

            Log::info('Old notifications cleaned', ['count' => $deleted]);
        })->dailyAt('03:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
