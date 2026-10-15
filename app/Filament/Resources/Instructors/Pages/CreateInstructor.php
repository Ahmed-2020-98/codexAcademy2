<?php

namespace App\Filament\Resources\Instructors\Pages;

use App\Enums\Role;
use App\Filament\Resources\Instructors\InstructorResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateInstructor extends CreateRecord
{
    protected static string $resource = InstructorResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $account = $data['account'];
            unset($data['account']);

            $user = User::create([
                'name' => $account['name'],
                'email' => $account['email'],
                'phone' => $account['phone'] ?? null,
                'password' => Hash::make(Str::random(16)),
            ]);
            $user->assignRole(Role::Instructor->value);

            return $user->instructorProfile()->create($data);
        });
    }
}
