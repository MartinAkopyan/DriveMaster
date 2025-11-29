<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminReportGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $reportPath,
        public string $reportFilename
    )
    {
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
        $downloadUrl = $this->reportPath;

        return (new MailMessage)
            ->subject('Admin report generated')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your requested admin report has been generated successfully.')
            ->line("Report: {$this->reportFilename}")
            ->action('Download Report', url($downloadUrl))
            ->line('The report will be available for 7 days.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'admin_report_generated',
            'message' => 'Admin report generated successfully',
            'filename' => $this->reportFilename,
            'path' => $this->reportPath,
        ];
    }
}
