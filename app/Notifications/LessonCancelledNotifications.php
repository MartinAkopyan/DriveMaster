<?php

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonCancelledNotifications extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Lesson $lesson,
        public ?User $cancelledBy,
        public ?string $reason,
        public bool $isSystemCancellation = false
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

        $lesson = $this->lesson;
        $message = (new MailMessage)->subject('Lesson Cancelled');

        if ($this->isSystemCancellation) {
            $message->line('A lesson has been automatically cancelled bu the system.')
                ->line('Reason: ' . $this->reason);
        } else {
            $cancelledByName = $this->cancelledBy ? $this->cancelledBy->name : 'Unknown';

            $message->line("A lesson has been cancelled by {$cancelledByName}.");

            if ($this->reason) {
                $message->line("Reason: {$this->reason}");
            }
        }

        $message->line("Date: {$lesson->start_time->format('F j, Y')}")
            ->line("Time: {$lesson->start_time->format('H:i')} - {$lesson->end_time->format('H:i')}")
            ->line("Instructor: {$lesson->instructor->name}")
            ->line("Student: {$lesson->student->name}");

        if ($notifiable->id === $lesson->instructor_id || $notifiable->id === $lesson->student_id) {
            $message->action('View Details', url("/lessons/{$lesson->id}"));
        }

        return $message;

    }

    public function toDatabase(object $notifiable): array
    {
        if ($this->isSystemCancellation) {
            $cancelledByName = 'System';
        } else {
            $cancelledByName = $this->cancelledBy ? $this->cancelledBy->name : 'Unknown';
        }

        return [
            'lesson_id' => $this->lesson->id,
            'type' => 'lesson_cancelled',
            'title' => 'Lesson Cancelled',
            'message' => "Lesson on {$this->lesson->start_time->format('M j, H:i')} cancelled by {$cancelledByName}",
            'reason' => $this->reason,
            'cancelled_by' => $this->cancelledBy?->id,
            'is_system_cancellation' => $this->isSystemCancellation,
            'start_time' => $this->lesson->start_time->toISOString(),
        ];
    }

}
