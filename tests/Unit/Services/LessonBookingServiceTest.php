<?php

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Exceptions\LessonBookingException;
use App\Models\User;
use App\Repositories\LessonRepository;
use App\Repositories\UserRepository;
use App\Services\LessonBookingService;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

class LessonBookingServiceTest extends TestCase
{
    protected LessonBookingService $service;
    protected  $userRepository;
    protected  $lessonRepository;

    protected User $student;
    protected User $instructor;
    protected string $date;
    protected int $slot = 1;
    protected int $instructorId = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = new User([
            'id' => 1,
            'role' => UserRole::STUDENT,
        ]);

        $this->instructor = new User([
            'id' => 2,
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => true
        ]);

        $this->date = Carbon::tomorrow()->format('Y-m-d');

        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->lessonRepository = Mockery::mock(LessonRepository::class);

        $this->service = new LessonBookingService($this->lessonRepository, $this->userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function throws_exception_when_user_is_not_student(): void
    {
        $nonStudent = new User([
            'id' => 3,
            'role' => UserRole::INSTRUCTOR,
            'is_active' => true,
        ]);

        $this->expectException(LessonBookingException::class);
        $this->expectExceptionMessage('Only students can book lessons');

        $this->service->bookLesson($nonStudent, $this->instructorId, $this->date, $this->slot);
    }

    /** @test */
    public function throws_exception_when_instructor_not_found(): void
    {
        $invalidInstructorId = 999;

        $this->userRepository
            ->shouldReceive('getApprovedInstructor')
            ->with($invalidInstructorId)
            ->once()
            ->andReturnNull();

        $this->expectException(LessonBookingException::class);
        $this->expectExceptionMessage('Instructor not found');

        $this->service->bookLesson($this->student, $invalidInstructorId, $this->date, $this->slot);
    }

    /** @test */
    public function throws_exception_when_slot_is_invalid(): void
    {

        $invalidSlot = 0;

        $this->userRepository->shouldReceive('getApprovedInstructor')
            ->with($this->instructorId)
            ->once()
            ->andReturn($this->instructor);

        $this->expectException(LessonBookingException::class);
        $this->expectExceptionMessage('Invalid slot number. Must be between 1 and 6');

        $this->service->bookLesson($this->student, $this->instructorId, $this->date, $invalidSlot);
    }

    /** @test */
    public function throws_exception_when_date_is_in_past(): void
    {
        $invalidDate = Carbon::yesterday()->format('Y-m-d');

        $this->userRepository->
            shouldReceive('getApprovedInstructor')
            ->with($this->instructorId)
            ->once()
            ->andReturn($this->instructor);

        $this->expectException(LessonBookingException::class);
        $this->expectExceptionMessage('Cannot book lessons in the past');

        $this->service->bookLesson($this->student, $this->instructorId, $invalidDate, $this->slot);
    }

}
