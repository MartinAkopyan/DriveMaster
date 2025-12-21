<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->createAdmin();

            $approvedInstructors = $this->createApprovedInstructors();
            $this->createPendingInstructors();
            $this->createRejectedInstructors();

            $students = $this->createStudents();

            $this->createLessons($approvedInstructors, $students);
        });

        $this->displayCredentials();
    }

    public function createAdmin(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@drivemaster.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
                'is_approved' => true
            ]
        );
    }

    private function createApprovedInstructors(): Collection
    {
        $mainInstructor = User::firstOrCreate(
            ['email' => 'john.smith@example.com'],
            User::factory()->instructor()->approved()->raw([
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
            ])
        );

        if (!$mainInstructor->profile) {
            Profile::create([
                'user_id' => $mainInstructor->id,
                'phone' => '+48123456789',
                'bio' => 'Experienced driving instructor with 10 years of teaching. Specializing in nervous beginners.',
                'experience_years' => 10,
                'car_model' => 'Toyota Corolla 2022',
            ]);
        }

        $otherInstructors = User::factory(4)
            ->instructor()
            ->approved()
            ->create();

        return collect([$mainInstructor])->merge($otherInstructors);
    }

    private function createPendingInstructors(): void
    {
        User::factory(3)
            ->instructor()
            ->create(['is_approved' => false]);
    }

    private function createRejectedInstructors(): void
    {
        $rejected = User::factory(2)
            ->instructor()
            ->create(['is_approved' => false]);

        foreach ($rejected as $instructor) {
            $instructor->profile->update([
                'rejection_reason' => fake()->randomElement([
                    'Insufficient driving experience',
                    'Invalid teaching credentials',
                    'Failed background check',
                ])
            ]);

            $instructor->delete();
        }
    }

    private function createStudents(): Collection
    {
        $mainStudent = User::firstOrCreate(
            ['email' => 'alice.student@example.com'],
            User::factory()->student()->raw([
                'name' => 'Alice Student',
                'email' => 'alice.student@example.com',
            ])
        );

        $otherStudents = User::factory(29)
            ->student()
            ->create();

        return collect([$mainStudent])->merge($otherStudents);
    }

    private function createLessons($instructors, $students): void
    {
        $instructorIds = $instructors->pluck('id')->toArray();
        $studentIds = $students->pluck('id')->toArray();

        $validStartHours = [8, 10, 12, 14, 16, 18];

        foreach (range(1, 30) as $_) {
            $daysAhead = rand(1, 14);
            $startHour = fake()->randomElement($validStartHours);

            Lesson::factory()->create([
                'instructor_id' => fake()->randomElement($instructorIds),
                'student_id' => fake()->randomElement($studentIds),
                'start_time' => now()->addDays($daysAhead)->setTime($startHour, 0, 0),
                'end_time' => now()->addDays($daysAhead)->setTime($startHour + 2, 0, 0),
                'status' => LessonStatus::PLANNED,
                'notes' => 'New lesson request',
            ]);
        }

        foreach (range(1, 40) as $_) {
            $daysAhead = rand(1, 30);
            $startHour = fake()->randomElement($validStartHours);

            Lesson::factory()->create([
                'instructor_id' => fake()->randomElement($instructorIds),
                'student_id' => fake()->randomElement($studentIds),
                'start_time' => now()->addDays($daysAhead)->setTime($startHour, 0, 0),
                'end_time' => now()->addDays($daysAhead)->setTime($startHour + 2, 0, 0),
                'status' => LessonStatus::CONFIRMED,
                'notes' => 'Confirmed lesson',
            ]);
        }

        foreach (range(1, 20) as $_) {
            $daysAgo = rand(1, 60);
            $startHour = fake()->randomElement($validStartHours);

            Lesson::factory()->create([
                'instructor_id' => fake()->randomElement($instructorIds),
                'student_id' => fake()->randomElement($studentIds),
                'start_time' => now()->subDays($daysAgo)->setTime($startHour, 0, 0),
                'end_time' => now()->subDays($daysAgo)->setTime($startHour + 2, 0, 0),
                'status' => LessonStatus::COMPLETED,
                'notes' => 'Successfully completed',
            ]);
        }

        $cancellationReasons = [
            'Student cancelled due to illness',
            'Instructor unavailable',
            'Weather conditions',
            'Student request - schedule conflict',
            'Car maintenance required',
        ];

        foreach (range(1, 10) as $_) {
            $daysAgo = rand(1, 30);
            $startHour = fake()->randomElement($validStartHours);

            $lesson = Lesson::factory()->create([
                'instructor_id' => fake()->randomElement($instructorIds),
                'student_id' => fake()->randomElement($studentIds),
                'start_time' => now()->subDays($daysAgo)->setTime($startHour, 0, 0),
                'end_time' => now()->subDays($daysAgo)->setTime($startHour + 2, 0, 0),
                'status' => LessonStatus::CANCELLED,
                'cancel_reason' => fake()->randomElement($cancellationReasons),
                'cancelled_by' => fake()->boolean()
                    ? fake()->randomElement($instructorIds)
                    : fake()->randomElement($studentIds),
            ]);
        }
    }

    private function displayCredentials(): void
    {
        $this->command->info('');
        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('🔑 Test credentials:');
        $this->command->info('   Admin: admin@drivemaster.com / password');
        $this->command->info('   Instructor: john.smith@example.com / password');
        $this->command->info('   Student: alice.student@example.com / password');
    }

}
