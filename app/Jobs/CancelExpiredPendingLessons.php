<?php

namespace App\Jobs;

use App\Services\LessonBookingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CancelExpiredPendingLessons implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $tries = 1;
    public $timeout = 120;

    public function handle(LessonBookingService $lessonService): void
    {
        $olderThan = Carbon::now()->subHours(24);

        $lessons = $lessonService->getExpiredPendingLessons($olderThan);

        $cancelledCount = 0;

        foreach ($lessons as $lesson) {
            try {
                $lessonService->cancelLessonAutomatically($lesson, 'Automatically cancelled: no confirmation within 24 hours');
                $cancelledCount++;
            } catch (\Exception $e) {
                Log::error('Failed to auto-cancel lesson', [
                    'lesson_id' => $lesson->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Expired pending lessons check completed', [
            'cancelled_count' => $cancelledCount,
            'checked_count' => $lessons->count(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CancelExpiredPendingLessons job failed', [
            'error' => $exception->getMessage(),
        ]);
    }

}
