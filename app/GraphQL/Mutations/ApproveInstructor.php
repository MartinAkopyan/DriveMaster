<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Exceptions\InstructorApprovalException;
use App\Models\User;
use App\Services\InstructorApprovalService;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\SelectFields;

class ApproveInstructor extends Mutation
{
    protected $attributes = [
        'name' => 'approveInstructor',
        'description' => 'Mutation to approve an instructor (admin only)'
    ];

    public function __construct(
        private readonly InstructorApprovalService $approvalService
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
                'description' => 'ID of instructor to approve'
            ]
        ];
    }

    /**
     * @throws InstructorApprovalException
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): User
    {

        $admin = auth()->user();

        return $this->approvalService->approveInstructor($args['instructor_id'], $admin);
    }
}
