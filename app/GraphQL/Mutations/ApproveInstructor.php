<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\User;
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

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of instructor to approve'
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {

        $admin = auth()->user();

        if (!$admin || !$admin->isAdmin()) {
            throw new \Exception('Unauthorized: Only admins can approve instructors.');
        }


        $instructor = User::findOrFail($args['id']);

        if (!$instructor->isInstructor()) {
            throw new \Exception('User is not an instructor.');
        }

        $instructor->update([
            'is_approved' => true,
        ]);

        return $instructor;
    }
}
