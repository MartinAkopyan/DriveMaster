<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstructorApprovedNotifications extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->onQueue('high');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Instructor Application Approved')
            ->greeting("Congratulations {$notifiable->name}")
            ->line('We are pleased to inform you that your instructor application has been approved!')
            ->line('You can now:')
            ->line('• Accept lesson bookings from students')
            ->line('• Set your availability schedule')
            ->line('• View and manage your lessons')
            ->action('Go to Dashboard', url('/dashboard'))
            ->line('Welcome to our DriveMaster school team!')
            ->line('If you have any questions, please don\'t hesitate to contact out support team.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'instructor_approved',
            'title' => 'Application Approved',
            'message' => 'Congratulations! Your instructor application has been approved. You can now start accepting lessons.',
            'approved_at' => now()->toISOString(),
        ];
    }
}
