<?php

use App\Enums\LessonStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users');
            $table->foreignId('student_id')->constrained('users');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->enum('status', ['planned', 'confirmed', 'completed', 'cancelled'])->default(LessonStatus::PLANNED);
            $table->text('notes')->nullable();
            $table->index(['instructor_id', 'start_time']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
