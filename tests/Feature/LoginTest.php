<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\User\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => 'Active',
        ]);

        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
        $this->assertGuest();
    }

    public function test_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password' => bcrypt('password123'),
            'status' => 'Active',
        ]);

        // Login first via actual POST to establish proper session
        $this->post('/login', [
            'login' => 'logout@example.com',
            'password' => 'password123',
        ]);
        $this->assertAuthenticated();

        $response = $this->post(route('logout'));
        $response->assertRedirect();
        $this->assertGuest();
    }
}
