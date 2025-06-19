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
            'PLANNED' => LessonStatus::PLANNED->value,
            'CONFIRMED' => LessonStatus::CONFIRMED->value,
            'COMPLETED' => LessonStatus::COMPLETED->value,
            'CANCELLED' => LessonStatus::CANCELLED->value,
        ],
    ];
}
