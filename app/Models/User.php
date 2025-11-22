<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property bool $is_approved
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Profile|null $profile
 * @property-read Collection|Lesson[] $lessonsAsInstructor
 * @property-read Collection|Lesson[] $lessonsAsStudent
 */

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_approved',
        'user_avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'is_approved' => 'boolean',
        'role' => UserRole::class,
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function lessonsAsInstructor(): HasMany
    {
        return $this->hasMany(Lesson::class, 'instructor_id');
    }

    public function lessonsAsStudent(): HasMany
    {
        return $this->hasMany(Lesson::class,  'student_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isInstructor(): bool
    {
        return $this->role === UserRole::INSTRUCTOR;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::STUDENT;
    }
}
