<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->createRoleWithPermissions(
        'ListOrdering_Test',
        ['mobile-room-list', 'mobile-tenant-list'],
        $this->user,
    );
    $this->actingAs($this->user);
});

afterEach(function () {
    $this->user->roles()->detach();
    $this->user->delete();
});

it('orders the room list by id descending when created_at values tie', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();

    $rooms = Room::factory()->count(5)->create(['pg_id' => $pg->id]);
    DB::table('pg_rooms')->update(['created_at' => now()->subMinute()]);

    $response = $this->getJson(route('api.room.index', ['pg_id' => $pg->id, 'per_page' => 10]))
        ->assertOk();

    $returned = collect($response->json('data'))->pluck('id')->all();
    $expected = $rooms->pluck('id')->sortDesc()->map(fn ($id) => (string) $id)->values()->all();

    expect($returned)->toBe($expected);
});

it('orders the tenant list by id descending when created_at values tie', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'bed_capacity' => 5]);

    $ids = [];
    foreach (['A', 'B', 'C', 'D', 'E'] as $bed) {
        $ids[] = Tenant::create([
            'name' => "Tenant {$bed}",
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'bed_no' => $bed,
            'status' => 'active',
        ])->id;
    }
    DB::table('tenants')->update(['created_at' => now()->subMinute()]);

    $response = $this->getJson(route('api.tenant.index', ['pg_id' => $pg->id, 'per_page' => 10]))
        ->assertOk();

    $returned = collect($response->json('data'))->pluck('id')->all();
    $expected = collect($ids)->sortDesc()->map(fn ($id) => (string) $id)->values()->all();

    expect($returned)->toBe($expected);
});
