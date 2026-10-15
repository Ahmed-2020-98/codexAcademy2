<?php

namespace Tests;

use App\Enums\CourseType;
use App\Enums\Role;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(Role $role, array $attributes = []): User
    {
        $user = User::factory()->create(['phone' => '+20100'.random_int(1000000, 9999999)] + $attributes);
        $user->assignRole($role->value);

        return $user;
    }

    protected function makeCourse(CourseType $type = CourseType::Recorded, array $attributes = []): Course
    {
        $department = Department::firstOrCreate(['slug' => 'english'], ['name' => 'English']);

        return Course::create($attributes + [
            'department_id' => $department->id,
            'title' => 'Course '.Str::random(5),
            'slug' => 'course-'.Str::lower(Str::random(8)),
            'type' => $type,
            'price' => 1000,
        ]);
    }
}
