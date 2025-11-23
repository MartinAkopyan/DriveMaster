<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Repositories\UserRepository;
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
        private readonly UserRepository $userRepo
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
        $user = auth()->user();

        if (!$user->isAdmin()) {
            throw new \Exception('Unauthorized: only admins allowed');
        }

        return $this->userRepo->getPendingInstructors();
    }
}
