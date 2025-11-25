<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstructorRejectedNotifications extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ?string $reason
    )
    {
        $this->onQueue('default');
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
        $message = (new MailMessage)
            ->subject('Instructor Application Status')
            ->greeting("Dear {$notifiable->name},")
            ->line('Thank you for your interest in becoming an instructor at our driving school.')
            ->line('After careful review of your application, we regret to inform you that we are unable to approve your application at this time.');

        if ($this->reason) {
            $message->line("Feedback: {$this->reason}");
        }

        $message->line('We encourage you to address any feedback provided and consider reapplying in the future.')
            ->line('If you have questions about this decision, please contact our support team.')
            ->action('Contact Support', url('/contact'))
            ->line('Thank you for your understanding.');

        return $message;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'instructor_rejected',
            'title' => 'Application Not Approved',
            'message' => 'Your instructor application was not approved. Please check your email for details.',
            'reason' => $this->reason,
            'rejected_at' => now()->toISOString(),
        ];
    }
}
