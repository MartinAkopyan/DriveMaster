<?php

namespace App\Enums;

enum LessonStatus: string {
    case PLANNED = 'planned';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
