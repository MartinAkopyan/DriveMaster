<?php

declare(strict_types=1);

namespace App\GraphQL\Enums;

use Rebing\GraphQL\Support\EnumType;
use App\Enums\UserRole;

class UserRoleEnum extends EnumType
{
    protected $attributes = [
        'name' => 'UserRoleEnum',
        'description' => 'The types of user roles',
        'values' => [
            'admin' => UserRole::ADMIN->value,
            'instructor' => UserRole::INSTRUCTOR->value,
            'student' => UserRole::STUDENT->value,
        ],
    ];
}
