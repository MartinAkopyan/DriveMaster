<?php

namespace App\Listeners;

use App\Events\InstructorApproved;
use App\Notifications\InstructorApprovedNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendInstructorApprovalNotifications implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Handle the event.
     */
    public function handle(InstructorApproved $event): void
    {
        $instructor = $event->instructor;

        try {
            $instructor->notify(new InstructorApprovedNotifications());

            Log::info('Instructor approval notification sent', [
                'instructor_id' => $instructor->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send approval notification', [
                'instructor_id' => $instructor->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(InstructorApproved $event, \Throwable $exception): void
    {
        Log::error('SendApprovalNotifications job failed', [
            'instructor_id' => $event->instructor->id,
            'error' => $exception->getMessage(),
        ]);
    }

}
