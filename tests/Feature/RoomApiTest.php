<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\PgManagement\Models\PgManagement;
use Modules\Role\Models\Role;
use Modules\Room\Models\Room;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->createRoleWithPermissions('RoomApi_Test', ['mobile-room-view', 'mobile-room-list'], $this->user);
    $this->createPermissions(['mobile-room-view', 'mobile-room-list']);
    $role = Role::findByName('RoomApi_Test');
    $role->syncPermissions(['mobile-room-view', 'mobile-room-list']);
    $this->actingAs($this->user);
});

afterEach(function () {
    $this->user->roles()->detach();
    $this->user->delete();
});

it('returns the room detail through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'bed_capacity' => 3]);

    $this->getJson(route('api.room.show', $room->public_id))
        ->assertOk()
        ->assertJsonPath('data.room_no', $room->room_no)
        ->assertJsonPath('data.bed_capacity', 3);
});

it('returns the full tenant name in bed_tenants', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'bed_capacity' => 3]);

    Tenant::create([
        'name' => 'Ram Sharma',
        'pg_id' => $pg->id,
        'room_id' => $room->id,
        'bed_no' => 'A',
        'status' => 'active',
    ]);

    $this->getJson(route('api.room.show', $room->public_id))
        ->assertOk()
        ->assertJsonPath('data.occupied_beds', ['A'])
        ->assertJsonPath('data.bed_tenants.A', 'Ram Sharma');
});

it('does not shorten multi-word tenant names in bed_tenants', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'bed_capacity' => 3]);

    Tenant::create([
        'name' => 'Mohan Lal Verma',
        'pg_id' => $pg->id,
        'room_id' => $room->id,
        'bed_no' => 'B',
        'status' => 'active',
    ]);

    $this->getJson(route('api.room.show', $room->public_id))
        ->assertOk()
        ->assertJsonPath('data.bed_tenants.B', 'Mohan Lal Verma');
});
