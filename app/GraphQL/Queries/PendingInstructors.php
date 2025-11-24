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

class PendingInstructors extends Query
{
    protected $attributes = [
        'name' => 'pendingInstructors',
        'description' => 'A query for a pending instructor as admin',
    ];

    public function __construct(
        private readonly InstructorService $instructorService
    ){}

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('User'));
    }

    public function args(): array
    {
        return [

        ];
    }

    /**
     * @throws \Exception
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Collection
    {
        $admin = auth()->user();

        return $this->instructorService->getPendingInstructors($admin);
    }
}
