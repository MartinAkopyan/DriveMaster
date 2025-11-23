<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
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
        protected readonly UserRepository $userRepo
    ){}

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('User'));
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Collection
    {
        \DB::enableQueryLog();

        $result = $this->userRepo->getApprovedInstructors();

        \Log::info(\DB::getQueryLog());

        return $result;
    }
}
