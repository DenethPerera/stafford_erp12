<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function createTestUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'user'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ], $attributes));
    }
}
