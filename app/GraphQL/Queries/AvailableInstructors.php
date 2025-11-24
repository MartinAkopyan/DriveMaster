<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Services\InstructorService;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;

class AvailableInstructors extends Query
{
    protected $attributes = [
        'name' => 'availableInstructors',
        'description' => 'A query for available instructors',
    ];

    public function __construct(
        protected readonly InstructorService $instructorService
    ){}

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('User'));
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Collection
    {
        return $this->instructorService->getAvailableInstructors();
    }
}
