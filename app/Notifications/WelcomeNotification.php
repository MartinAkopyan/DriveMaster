<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Welcome to DriveMaster!')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Thank you for registering at DriveMaster driving school.');

        if ($notifiable->isStudent()) {
            $message
                ->line('You can now browse available instructors and book your first lesson.')
                ->action('Browse Instructors', url('/instructors'))
                ->line('Good luck on your driving journey!');
        }

        if ($notifiable->isInstructor()) {
            $message
                ->line('Your account is pending approval. An administrator will review your application shortly.');
        }

        return $message->line('If you have any questions, feel free to contact our support team.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
