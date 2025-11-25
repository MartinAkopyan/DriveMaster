<?php

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonCreatedNotifications extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Lesson $lesson,
        public bool $isInstructor
    ){
        $this->onQueue($isInstructor ? 'high' : 'default');
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
                ->subject('New lesson booked')
                ->greeting("Hello {$this->lesson->instructor->name}")
                ->line('A new lesson has been booked with you.')
                ->line("Student: {$this->lesson->student->name}")
                ->line("Date: {$this->lesson->start_time->format('F j, Y')}")
                ->line("Time: {$this->lesson->start_time->format('H:i')} - {$this->lesson->end_time->format('H:i')}")
                ->action('View Lesson Details', url("/lessons/{$this->lesson->id}"))
                ->line('Please confirm or adjust the lesson time.');
        }

        return (new MailMessage)
            ->subject('Lesson Booking Confirmation')
            ->greeting("Hello {$this->lesson->student->name}!")
            ->line("Your driving lesson has been booked successfully!")
            ->line("Instructor: {$this->lesson->instructor->name}")
            ->line("Date: {$this->lesson->start_time->format('F j, Y')}")
            ->line("Time: {$this->lesson->start_time->format('H:i')} - {$this->lesson->end_time->format('H:i')}")
            ->action('View Lesson Details', url("/lessons/{$this->lesson->id}"))
            ->line('Waiting for instructor confirmation.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'type' => 'lesson_created',
            'message' => $this->isInstructor
                ? "New lesson booked with {$this->lesson->student->name}"
                : "Lesson booked with {$this->lesson->instructor->name}",
            'start_time' => $this->lesson->start_time,
        ];
    }
}
