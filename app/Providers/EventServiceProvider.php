<?php

namespace App\Providers;

use App\Events\InstructorApproved;
use App\Events\InstructorRejected;
use App\Events\LessonCancelled;
use App\Events\LessonCancelledBySystem;
use App\Events\LessonConfirmed;
use App\Events\LessonCreated;
use App\Listeners\InvalidateInstructorCache;
use App\Listeners\SendInstructorApprovalNotifications;
use App\Listeners\SendInstructorRejectionNotifications;
use App\Listeners\SendLessonCancellationNotifications;
use App\Listeners\SendLessonConfirmationNotifications;
use App\Listeners\SendLessonCreationNotifications;
use App\Listeners\SendSystemLessonCancellationNotifications;
use App\Listeners\UpdateLessonCache;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        InstructorApproved::class => [
            SendInstructorApprovalNotifications::class,
            InvalidateInstructorCache::class
        ],
        InstructorRejected::class => [
            SendInstructorRejectionNotifications::class,
            InvalidateInstructorCache::class
        ],
        LessonCreated::class => [
            SendLessonCreationNotifications::class,
            UpdateLessonCache::class
        ],
        LessonConfirmed::class => [
            SendLessonConfirmationNotifications::class,
            UpdateLessonCache::class
        ],
        LessonCancelled::class => [
            SendLessonCancellationNotifications::class,
            UpdateLessonCache::class
        ],
        LessonCancelledBySystem::class => [
            SendSystemLessonCancellationNotifications::class,
            UpdateLessonCache::class
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
