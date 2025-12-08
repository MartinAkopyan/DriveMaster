<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Lesson
 *
 * @property int $id
 * @property int $instructor_id
 * @property int $student_id
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property LessonStatus $status
 * @property string|null $notes
 * @property int|null $cancelled_by
 * @property string|null $cancel_reason
 *
 * @property-read User $instructor
 * @property-read User $student
 */
class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'student_id',
        'start_time',
        'end_time',
        'status',
        'notes',
        'cancelled_by',
        'cancel_reason'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => LessonStatus::class,
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

}
