<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $phone
 * @property string|null $bio
 * @property int|null $experience_years
 * @property string|null $car_model
 * @property string|null $rejection_reason
 * @property Carbon|null $rejected_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User $user
 */

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'bio',
        'experience_years',
        'car_model',
        'rejection_reason'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
