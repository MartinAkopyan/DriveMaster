<?php

declare(strict_types=1);

namespace App\GraphQL\Enums;

use Rebing\GraphQL\Support\EnumType;
use App\Enums\LessonStatus;

class LessonStatusEnum extends EnumType
{
    protected $attributes = [
        'name' => 'LessonStatusEnum',
        'description' => 'The types of lesson status',
        'values' => [
            'planned'   => LessonStatus::PLANNED->value,
            'confirmed' => LessonStatus::CONFIRMED->value,
            'completed' => LessonStatus::COMPLETED->value,
            'cancelled' => LessonStatus::CANCELLED->value,
        ],
    ];
}
