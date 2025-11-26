<?php

namespace App\Listeners;

use App\Events\InstructorRejected;
use App\Notifications\InstructorRejectedNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendInstructorRejectionNotifications implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Handle the event.
     */
    public function handle(InstructorRejected $event): void
    {
        $instructor = $event->instructor;
        $reason = $event->reason;


        try {
            $instructor->notify(new InstructorRejectedNotifications($reason));

            Log::info('Instructor rejection notification sent', [
               'instructor_id' => $instructor->id,
               'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send rejection notification', [
                'instructor_id' => $instructor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(InstructorRejected $event, \Throwable $exception): void
    {
        Log::error('SendRejectionNotifications job failed', [
            'instructor_id' => $event->instructor->id,
            'error' => $exception->getMessage(),
        ]);
    }


}
