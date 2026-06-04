<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Setting\Models\Setting;
use Modules\User\Models\User;
use Tests\TestCase;

class DashbordTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // Dashboard layout accesses Auth::user()->roles[0]->name, so user needs a role
        $this->createRoleWithPermissions('test-role', [], $this->user);
        $this->actingAs($this->user);

        Setting::factory()->create();
        $this->seedYear();
    }

    public function test_can_view_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }
}
