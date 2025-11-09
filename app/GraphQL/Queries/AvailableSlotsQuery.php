<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use Carbon\Carbon;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;

class AvailableSlotsQuery extends Query
{
    protected $attributes = [
        'name' => 'availableSlots',
        'description' => 'A query for available slots',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Slot'));
    }

    public function args(): array
    {
        return [
            'instructor_id' => Type::nonNull(Type::int()),
            'date' => Type::nonNull(Type::string()),
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {

        $instructorId = $args['instructor_id'];
        $date = Carbon::parse($args['date']);

        $workStart = $date->copy()->setTime(8,0);
        $workEnd = $date->copy()->setTime(20, 0);
        $slotDuration = 2;

        $occupied = Lesson::where('instructor_id', $instructorId)
            ->whereDate('start_time', $date)
            ->whereIn('status', [LessonStatus::PLANNED->value, LessonStatus::CONFIRMED->value])
            ->get(['start_time', 'end_time']);

        $slots = [];
        $current = $workStart->copy();

        while ($current->lt($workEnd)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addHours($slotDuration);

            $conflict = $occupied->first(function ($lesson) use ($slotStart, $slotEnd) {
               return $lesson->start_time < $slotEnd && $lesson->end_time > $slotStart;
            });

            if (!$conflict && $slotEnd->lt($workEnd)) {
                $slots[] = [
                    'start_time' => $slotStart->toDateTimeString(),
                    'end_time' => $slotEnd->toDateTimeString(),
                ];
            }

            $current->addHours($slotDuration);
        }

        return $slots;
    }
}
