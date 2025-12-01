<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Exceptions\InstructorApprovalException;
use App\Services\InstructorService;
use App\Models\User;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\SelectFields;

class RejectInstructor extends Mutation
{
    protected $attributes = [
        'name' => 'rejectInstructor',
        'description' => 'A mutation to reject instructor'
    ];

    public function __construct(
        private readonly InstructorService $instructorService
    ){}

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function args(): array
    {
        return [
            'instructor_id' => [
                'type' => Type::nonNull(Type::int()),
            ],
            'reason' => [
                'type' => Type::string(),
            ]
        ];
    }

    /**
     * @throws InstructorApprovalException
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): User
    {
        $admin = auth()->user();

        return $this->instructorService->rejectInstructor($args['instructor_id'], $admin, $args['reason'] ?? null);

    }
}
