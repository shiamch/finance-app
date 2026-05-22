<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /** An authenticated user can log out and becomes a guest. */
    public function test_authenticated_user_can_log_out(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->withHeader('Origin', 'http://localhost')->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])->assertOk();

        $response = $this->withHeader('Origin', 'http://localhost')->postJson('/api/logout');

        $response->assertNoContent();
        $this->withHeader('Origin', 'http://localhost')->getJson('/api/me')->assertUnauthorized();
    }

    /** Guests cannot call the logout endpoint. */
    public function test_guest_cannot_log_out(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }
}
