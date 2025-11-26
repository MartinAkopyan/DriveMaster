<?php

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonReminderNotifications extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lesson $lesson,
        public ?bool $isInstructor = false,
    )
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
        $message = (new MailMessage)
            ->subject('Lesson Reminder - Tomorrow')
            ->greeting("Hello {$notifiable->name}");

        if ($this->isInstructor) {
            $message->line("Reminder: You have a lesson tomorrow with {$this->lesson->student->name}");
        } else {
            $message->line("Reminder: You have a driving lesson tomorrow with {$this->lesson->instructor->name}");
        }

        return $message
            ->line("Date: {$this->lesson->start_time->format('F j, Y')}")
            ->line("Time: {$this->lesson->start_time->format('H:i')} - {$this->lesson->end_time->format('H:i')}")
            ->action('View Lesson', url("/lessons/{$this->lesson->id}"))
            ->line('See you tomorrow!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'type' => 'lesson_reminder',
            'message' => "Reminder: Lesson tomorrow at {$this->lesson->start_time->format('H:i')}",
            'start_time' => $this->lesson->start_time->toISOString(),
        ];
    }
}
