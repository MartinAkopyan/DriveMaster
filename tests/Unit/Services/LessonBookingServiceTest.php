<?php

namespace Tests\Unit\Services;

use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Events\LessonCreated;
use App\Exceptions\LessonBookingException;
use App\Models\Lesson;
use App\Models\User;
use App\Repositories\LessonRepository;
use App\Repositories\UserRepository;
use App\Services\LessonBookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

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

        $this->student = new User();
        $this->student->setAttribute('id', 1);
        $this->student->setAttribute('role', UserRole::STUDENT);

        $this->instructor = new User();
        $this->instructor->setAttribute('id', 2);
        $this->instructor->setAttribute('role', UserRole::INSTRUCTOR);
        $this->instructor->setAttribute('is_approved', true);

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

    /** @test */
    public function throws_exception_when_time_slot_already_booked(): void
    {
        $this->userRepository
            ->shouldReceive('getApprovedInstructor')
            ->with($this->instructorId)
            ->once()
            ->andReturn($this->instructor);

        $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $lock->shouldReceive('block')
            ->once()
            ->andReturnUsing(function ($timeout, $callback) {
                return $callback();
            });

        Cache::shouldReceive('lock')
            ->once()
            ->andReturn($lock);


        $this->lessonRepository
            ->shouldReceive('hasInstructorConflict')
            ->once()
            ->andReturn(true);

        $this->expectException(LessonBookingException::class);
        $this->expectExceptionMessage('This time slot is already booked');

        $this->service->bookLesson($this->student, $this->instructorId, $this->date, $this->slot);
    }

    /** @test */
    public function successfully_books_lesson_and_dispatches_event(): void
    {
        Event::fake();

        $createdLesson = new Lesson([
            'id' => 1,
            'instructor_id' => $this->instructorId,
            'student_id' => $this->student->id,
            'start_time' => Carbon::parse($this->date)->setTime(8,0),
            'end_time' => Carbon::parse($this->date)->setTime(10,0),
            'status' => LessonStatus::PLANNED,
        ]);

        $this->userRepository
            ->shouldReceive('getApprovedInstructor')
            ->with($this->instructorId)
            ->once()
            ->andReturn($this->instructor);

        $this->lessonRepository
            ->shouldReceive('hasInstructorConflict')
            ->once()
            ->andReturn(false);

        $this->lessonRepository
            ->shouldReceive('createLesson')
            ->once()
            ->andReturn($createdLesson);

        $result = $this->service->bookLesson($this->student, $this->instructorId, $this->date, $this->slot);

        $this->assertInstanceOf(Lesson::class, $result);
        $this->assertEquals(LessonStatus::PLANNED, $result->status);
        $this->assertEquals($this->instructorId, $result->instructor_id);
        $this->assertEquals($this->student->id, $result->student_id);

        Event::assertDispatched(LessonCreated::class, function ($event) use ($createdLesson) {
            return $event->lesson->id === $createdLesson->id;
        });
    }

    /** @test */
    public function uses_lock_to_prevent_race_conditions(): void
    {
        Event::fake();

        $this->userRepository
            ->shouldReceive('getApprovedInstructor')
            ->once()
            ->andReturn($this->instructor);

        $this->lessonRepository
            ->shouldReceive('hasInstructorConflict')
            ->once()
            ->andReturn(false);

        $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);

        $lock->shouldReceive('block')
            ->with(5, Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($timeout, $callback) {
                return $callback();
            });

        Cache::shouldReceive('lock')
            ->once()
            ->with(Mockery::type('string'), 10)
            ->andReturn($lock);

        $this->lessonRepository
            ->shouldReceive('createLesson')
            ->once()
            ->andReturn(new Lesson());

        $result = $this->service->bookLesson(
            $this->student,
            $this->instructorId,
            $this->date,
            $this->slot
        );

        $this->assertInstanceOf(Lesson::class, $result);
        Cache::shouldHaveReceived('lock')->once();
    }

    /** @test */
    public function throws_exception_when_distributed_lock_times_out(): void
    {
        Event::fake();

        $this->userRepository
            ->shouldReceive('getApprovedInstructor')
            ->once()
            ->andReturn($this->instructor);

        $this->lessonRepository
            ->shouldReceive('hasInstructorConflict')
            ->never();

        $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);

        $lock->shouldReceive('block')
            ->once()
            ->with(5, Mockery::type(\Closure::class))
            ->andThrow(new LessonBookingException());

        Cache::shouldReceive('lock')
            ->once()
            ->with(Mockery::type('string'), 10)
            ->andReturn($lock);

        $this->expectException(LessonBookingException::class);
        $this->expectExceptionMessage(
            'Too many simultaneous booking attempts'
        );

        $this->service->bookLesson($this->student, $this->instructorId, $this->date, $this->slot);
    }

    /** @test */
    public function calculates_slot_time_correctly(): void
    {
        Event::fake();

        $expectedStartTime = Carbon::parse($this->date)->setTime(8,0);
        $expectedEndTime = $expectedStartTime->copy()->addHours(2);

        $this->userRepository
            ->shouldReceive('getApprovedInstructor')
            ->with($this->instructorId)
            ->once()
            ->andReturn($this->instructor);

        $this->lessonRepository
            ->shouldReceive('hasInstructorConflict')
            ->once()
            ->andReturn(false);

        $this->lessonRepository
            ->shouldReceive('createLesson')
            ->once()
            ->andReturnUsing(function ($data) use ($expectedStartTime, $expectedEndTime) {
                $this->assertEquals(
                    $expectedStartTime->format('Y-m-d H:i:s'),
                    $data['start_time']->format('Y-m-d H:i:s')
                );
                $this->assertEquals(
                    $expectedEndTime->format('Y-m-d H:i:s'),
                    $data['end_time']->format('Y-m-d H:i:s')
                );

                return new Lesson($data);
            });

        $this->service->bookLesson($this->student, $this->instructorId, $this->date, $this->slot);
    }

}
