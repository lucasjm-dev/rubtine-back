<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValidationResponseTest extends TestCase
{
    /** @test */
    public function it_returns_snake_case_error_codes_for_validation_failures()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'invalid-email',
            'password' => 'short',
            // Missing password_confirmation to trigger confirmed rule
            // Missing email to trigger required rule (wait, I provided invalid email)
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors' => [
                    'email',
                    'password',
                ],
            ]);

        $errors = $response->json('errors');

        // Check for specific error codes
        $this->assertContains('invalid_email', $errors['email']);
        $this->assertContains('too_short', $errors['password']);
        $this->assertContains('confirmation_does_not_match', $errors['password']);
    }

    /** @test */
    public function it_returns_required_code_for_missing_fields()
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422);

        $errors = $response->json('errors');

        $this->assertContains('required', $errors['email']);
        $this->assertContains('required', $errors['password']);
    }
}
