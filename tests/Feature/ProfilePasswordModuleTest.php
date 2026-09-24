<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->createRoleWithPermissions('ProfilePassword_Test', [], $this->user);
    $this->actingAs($this->user);
});

afterEach(function () {
    $this->user->roles()->detach();
    $this->user->delete();
});

it('changes the password with a matching current password', function () {
    Sanctum::actingAs($this->user);

    $this->postJson(route('api.profile.change-password'), [
        'current_password' => 'password',
        'password' => 'NewPass@123',
        'confirm_password' => 'NewPass@123',
    ])->assertOk()
        ->assertJsonPath('message', 'Password updated successfully.');

    expect(Hash::check('NewPass@123', $this->user->fresh()->password))->toBeTrue();
});

it('rejects a wrong current password', function () {
    Sanctum::actingAs($this->user);

    $this->postJson(route('api.profile.change-password'), [
        'current_password' => 'wrong-password',
        'password' => 'NewPass@123',
        'confirm_password' => 'NewPass@123',
    ])->assertStatus(403)
        ->assertJsonPath('message', 'Current password does not match.');

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

it('rejects a weak new password', function () {
    Sanctum::actingAs($this->user);

    $this->postJson(route('api.profile.change-password'), [
        'current_password' => 'password',
        'password' => 'weak',
        'confirm_password' => 'weak',
    ])->assertStatus(422);
});

it('rejects a mismatched confirmation password', function () {
    Sanctum::actingAs($this->user);

    $this->postJson(route('api.profile.change-password'), [
        'current_password' => 'password',
        'password' => 'NewPass@123',
        'confirm_password' => 'Different@123',
    ])->assertStatus(422);
});

it('requires authentication to change the password', function () {
    auth()->logout();
    $this->app['auth']->forgetGuards();

    $this->postJson(route('api.profile.change-password'), [
        'current_password' => 'password',
        'password' => 'NewPass@123',
        'confirm_password' => 'NewPass@123',
    ])->assertStatus(401);
});
