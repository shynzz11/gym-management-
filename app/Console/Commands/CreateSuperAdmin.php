<?php

namespace App\Console\Commands;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdmin extends Command
{
    protected $signature = 'gym:create-super-admin';

    protected $description = 'Create the single production super admin account interactively';

    public function handle(): int
    {
        if (User::query()->where('type', UserType::SUPER_ADMIN)->exists()) {
            $this->error('A super admin already exists. Aborting.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->ask('Name'),
            'email' => $this->ask('Email'),
            'password' => $this->secret('Password (min 12 chars, mixed case, number, symbol)'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'type' => UserType::SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $user->adminProfile()->create();

        $this->info("Super admin created: {$user->email}");

        return self::SUCCESS;
    }
}
