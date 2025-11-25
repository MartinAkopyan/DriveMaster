<?php

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonConfirmedNotifications extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Lesson $lesson,
        public bool   $isInstructor
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
        if ($this->isInstructor) {
            return (new MailMessage)
                ->subject('Lesson Confirmed')
                ->line('You have successfully confirmed your lesson.')
                ->line("Student: {$this->lesson->student->name}")
                ->line("Date: {$lesson->start_time->format('F j, Y')}")
                ->line("Time: {$lesson->start_time->format('H:i')} - {$lesson->end_time->format('H:i')}")
                ->action('View Lesson Details', url("/lessons/{$lesson->id}"))
                ->line('The student has been notified about the confirmation.');
        }

        return (new MailMessage)
            ->subject('Lesson Confirmed by Instructor')
            ->line("Great news! Your lesson has been confirmed!")
            ->line("Instructor: {$lesson->instructor->name}")
            ->line("Date: {$lesson->start_time->format('F j, Y')}")
            ->line("Time: {$lesson->start_time->format('H:i')} - {$lesson->end_time->format('H:i')}")
            ->action('View Lesson Details', url("/lessons/{$lesson->id}"))
            ->line('See you at the lesson!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'type' => 'lesson_confirmed',
            'title' => 'Lesson Confirmed',
            'message' => $this->isInstructor
                ? "You confirmed lesson with {$this->lesson->student->name}"
                : "Your lesson with {$this->lesson->instructor->name} is confirmed!",
            'status' => $this->lesson->status->value,
            'start_time' => $this->lesson->start_time->toISOString(),
        ];
    }
}
