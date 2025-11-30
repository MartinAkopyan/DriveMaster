<?php

namespace App\Listeners;

use App\Notifications\WelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {

        try {
            $event->user->notify(new WelcomeNotification());

            Log::info('User registration email sent', [
                'user_id' => $event->user->id,
                'email' => $event->user->email
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send user registration email', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
