<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Repositories\LessonRepository;
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

    public function __construct(
        private readonly LessonRepository $lessonRepo,
    ){}

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

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): array
    {
        return $this->lessonRepo->getAvailableSlots($args['instructor_id'], Carbon::parse($args['date']));
    }
}
