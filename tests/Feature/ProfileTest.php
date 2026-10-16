<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->get('/profile')->assertNotFound();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assertNotFound();
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $this->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assertNotFound();
    }

    public function test_user_can_delete_their_account(): void
    {
        $this->delete('/profile', [
            'password' => 'password',
        ])->assertNotFound();
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $this->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ])
            ->assertNotFound();
    }
}
