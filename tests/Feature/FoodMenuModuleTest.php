<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\FoodMenu\Models\FoodMenu;
use Modules\FoodMenu\Models\FoodMenuItem;
use Modules\PgManagement\Models\PgManagement;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->createRoleWithPermissions('FoodMenu_Test', ['foodmenu-list', 'foodmenu-create', 'foodmenu-show', 'foodmenu-edit', 'foodmenu-delete'], $this->user);
    $this->createPermissions(['mobile-foodmenu-list', 'mobile-foodmenu-view', 'mobile-foodmenu-create', 'mobile-foodmenu-edit', 'mobile-foodmenu-delete']);
    $role = Role::findByName('FoodMenu_Test');
    $role->syncPermissions(['foodmenu-list', 'foodmenu-create', 'foodmenu-show', 'foodmenu-edit', 'foodmenu-delete', 'mobile-foodmenu-list', 'mobile-foodmenu-view', 'mobile-foodmenu-create', 'mobile-foodmenu-edit', 'mobile-foodmenu-delete']);
    $this->actingAs($this->user);
});

afterEach(function () {
    $this->user->roles()->detach();
    $this->user->delete();
});

it('renders the food menu index page', function () {
    $this->get(route('foodmenu.index'))
        ->assertOk()
        ->assertSee('Food Menu');
});

it('serves server-side data for the food menu table', function () {
    $pg = PgManagement::factory()->create();
    FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id]);

    $this->getJson(route('foodmenu.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
});

it('creates a weekly food menu with inline items and logs it', function () {
    $pg = PgManagement::factory()->create();

    $response = $this->postJson(route('foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'Inline Weekly Menu',
        'week_start_date' => '2026-09-14',
        'status' => 'active',
        'week_items' => [
            'sunday' => ['breakfast' => 'Idli', 'lunch' => 'Curd Rice', 'dinner' => 'Parotta'],
            'monday' => ['breakfast' => 'Poha', 'lunch' => '', 'dinner' => 'Dal Tadka'],
        ],
    ]);

    $response->assertOk()->assertJson(['status_code' => 200]);

    $menu = FoodMenu::where('title', 'Inline Weekly Menu')->firstOrFail();
    expect($menu->pg_id)->toBe($pg->id);
    expect($menu->user_id)->toBe($this->user->id);
    expect($menu->menu_type)->toBe('weekly');
    expect($menu->week_start_date)->toBe('2026-09-14');

    $names = $menu->items()->pluck('item_name', 'meal_time')->mapWithKeys(
        fn ($name, $meal) => [$meal => $name]
    )->all();
    expect($menu->items()->count())->toBe(5);
    expect($menu->items()->where('day', 'sunday')->where('meal_time', 'breakfast')->first()->item_name)->toBe('Idli');
    $mondayLunch = $menu->items()->where('day', 'monday')->where('meal_time', 'lunch')->first();
    expect($mondayLunch)->toBeNull();

    $this->assertLogRecord('food_menu_logs', 'food_menu_id', $menu->id, 'created', $this->user->id, [
        'system_remark_contains' => 'Inline Weekly Menu',
        'expect_null_old_values' => true,
        'new_values_keys' => ['pg_id', 'title', 'menu_type', 'week_start_date'],
    ]);
});

it('creates a special day food menu with inline items', function () {
    $pg = PgManagement::factory()->create();

    $response = $this->postJson(route('foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'special',
        'title' => 'Inline Holi Special',
        'special_date' => '2026-09-29',
        'status' => 'active',
        'special_items' => [
            'breakfast' => 'Gujarati Thali',
            'lunch' => 'Thandai',
            'dinner' => 'Malpua',
        ],
    ]);

    $response->assertOk()->assertJson(['status_code' => 200]);

    $menu = FoodMenu::where('title', 'Inline Holi Special')->firstOrFail();
    expect($menu->menu_type)->toBe('special');
    expect($menu->special_date)->toBe('2026-09-29');

    expect($menu->items()->count())->toBe(3);
    expect($menu->items()->whereNull('day')->where('meal_time', 'dinner')->first()->item_name)->toBe('Malpua');
});

it('syncs inline items on update', function () {
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id, 'title' => 'Sync Weekly']);
    $menu->items()->create([
        'day' => 'monday',
        'meal_time' => 'breakfast',
        'item_name' => 'Old Poha',
        'sort_order' => 0,
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);

    $this->putJson(route('foodmenu.update', $menu->public_id), [
        'id' => $menu->public_id,
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'Sync Weekly',
        'week_start_date' => '2026-09-21',
        'status' => 'active',
        'week_items' => [
            'monday' => ['breakfast' => 'New Poha'],
        ],
    ])->assertOk()->assertJson(['status_code' => 200]);

    expect($menu->items()->count())->toBe(1);
    expect($menu->items()->where('day', 'monday')->where('meal_time', 'breakfast')->first()->item_name)->toBe('New Poha');
});

it('validates weekly food menu requires a week start date', function () {
    $pg = PgManagement::factory()->create();

    $this->postJson(route('foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'Missing Date Menu',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['week_start_date']);
});

it('validates special food menu requires a special date', function () {
    $pg = PgManagement::factory()->create();

    $this->postJson(route('foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'special',
        'title' => 'Missing Special Date',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['special_date']);
});

it('validates the menu title is required', function () {
    $pg = PgManagement::factory()->create();

    $this->postJson(route('foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'week_start_date' => '2026-09-14',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

it('shows a food menu as json', function () {
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id]);

    $this->getJson(route('foodmenu.show', $menu->public_id))
        ->assertOk()
        ->assertJson(['status_code' => 200])
        ->assertJsonPath('result.public_id', $menu->public_id);
});

it('updates a food menu and logs it', function () {
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id, 'title' => 'Before Edit']);

    $response = $this->putJson(route('foodmenu.update', $menu->public_id), [
        'id' => $menu->public_id,
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'After Edit',
        'week_start_date' => '2026-09-21',
        'status' => 'active',
    ]);

    $response->assertOk()->assertJson(['status_code' => 200]);

    expect($menu->fresh()->title)->toBe('After Edit');

    $this->assertLogRecord('food_menu_logs', 'food_menu_id', $menu->id, 'updated', $this->user->id, [
        'old_value_check' => ['title' => 'Before Edit'],
        'new_value_check' => ['title' => 'After Edit'],
    ]);
});

it('deletes a food menu softly and logs it', function () {
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id]);

    $this->deleteJson(route('foodmenu.destroy', $menu->public_id), [])
        ->assertOk()
        ->assertJson(['status_code' => 200]);

    expect(FoodMenu::find($menu->id))->toBeNull();
    expect(FoodMenu::withTrashed()->find($menu->id))->not->toBeNull();

    $this->assertLogRecord('food_menu_logs', 'food_menu_id', $menu->id, 'deleted', $this->user->id, [
        'system_remark_contains' => $menu->title,
        'expect_null_new_values' => true,
    ]);
});

it('scopes food menus to own pg for Pg_Admin users', function () {
    $pgAdmin = User::factory()->create();
    $role = Role::firstOrCreate(
        ['name' => 'Pg_Admin', 'guard_name' => 'web'],
        ['title' => 'Pg_Admin', 'title_tag' => 'Pg_Admin']
    );
    $role->givePermissionTo('foodmenu-list');
    $pgAdmin->assignRole($role);

    $myPg = PgManagement::factory()->create(['owner_id' => $pgAdmin->id]);
    $otherPg = PgManagement::factory()->create(['owner_id' => $this->user->id]);

    FoodMenu::factory()->create(['pg_id' => $myPg->id, 'user_id' => $pgAdmin->id, 'title' => 'My PG Menu']);
    FoodMenu::factory()->create(['pg_id' => $otherPg->id, 'user_id' => $this->user->id, 'title' => 'Other PG Menu']);

    $data = $this->actingAs($pgAdmin)
        ->getJson(route('foodmenu.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['title'])->toBe('My PG Menu');
});

it('serves server-side data for the food menu items table', function () {
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id]);
    FoodMenuItem::factory()->create(['food_menu_id' => $menu->id]);

    $this->getJson(route('foodmenu.items').'?food_menu_id='.$menu->id, ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
});

it('adds an item to a food menu', function () {
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id]);

    $response = $this->postJson(route('foodmenu.items.store'), [
        'food_menu_id' => $menu->id,
        'day' => 'monday',
        'meal_time' => 'breakfast',
        'item_name' => 'Poha',
        'status' => 'active',
    ]);

    $response->assertOk()->assertJson(['status_code' => 200]);

    $item = FoodMenuItem::where('food_menu_id', $menu->id)->firstOrFail();
    expect($item->item_name)->toBe('Poha');
    expect($item->day)->toBe('monday');
    expect($item->meal_time)->toBe('breakfast');
});

it('validates the menu item meal time is required', function () {
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id]);

    $this->postJson(route('foodmenu.items.store'), [
        'food_menu_id' => $menu->id,
        'item_name' => 'Missing Meal Time',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['meal_time']);
});

it('deletes a menu item softly', function () {
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id]);
    $item = FoodMenuItem::factory()->create(['food_menu_id' => $menu->id, 'item_name' => 'To Delete']);

    $this->deleteJson(route('foodmenu.items.destroy', $item->public_id), [])
        ->assertOk()
        ->assertJson(['status_code' => 200]);

    expect(FoodMenuItem::find($item->id))->toBeNull();
});

it('lists food menus through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id, 'title' => 'API Weekly']);

    $this->getJson(route('api.foodmenu.index'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta'])
        ->assertJsonCount(1, 'data');
});

it('creates a food menu through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();

    $this->postJson(route('api.foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'special',
        'title' => 'API Special',
        'special_date' => '2026-12-25',
    ])->assertCreated()
        ->assertJsonPath('data.title', 'API Special');
});

it('defaults menu status to active when not provided', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();

    $this->postJson(route('api.foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'special',
        'title' => 'Default Status Menu',
        'special_date' => '2026-12-26',
    ])->assertCreated()
        ->assertJsonPath('data.status', 'active');

    expect(FoodMenu::where('title', 'Default Status Menu')->first()->status)->toBe('active');
});

it('syncs inline weekly items through the api on create', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();

    $this->postJson(route('api.foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'API Weekly Items',
        'week_start_date' => '2026-09-21',
        'week_items' => [
            'sunday' => ['breakfast' => 'Idli', 'lunch' => 'Curd Rice', 'dinner' => 'Parotta'],
            'monday' => ['breakfast' => 'Poha', 'lunch' => '', 'dinner' => 'Dal Tadka'],
        ],
    ])->assertCreated();

    $menu = FoodMenu::where('title', 'API Weekly Items')->firstOrFail();
    expect($menu->items()->count())->toBe(5);
    expect($menu->items()->where('day', 'sunday')->where('meal_time', 'breakfast')->first()->item_name)->toBe('Idli');
    expect($menu->items()->where('day', 'monday')->where('meal_time', 'lunch')->first())->toBeNull();
});

it('syncs inline special items through the api on create', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();

    $this->postJson(route('api.foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'special',
        'title' => 'API Special Items',
        'special_date' => '2026-12-25',
        'special_items' => [
            'breakfast' => 'Thali',
            'lunch' => 'Thandai',
            'dinner' => '',
        ],
    ])->assertCreated();

    $menu = FoodMenu::where('title', 'API Special Items')->firstOrFail();
    expect($menu->items()->count())->toBe(2);
    expect($menu->items()->whereNull('day')->where('meal_time', 'breakfast')->first()->item_name)->toBe('Thali');
});

it('syncs inline weekly items through the api on update', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id, 'title' => 'API Sync']);

    $this->putJson(route('api.foodmenu.update', $menu->public_id), [
        'id' => $menu->public_id,
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'API Sync',
        'week_start_date' => '2026-09-28',
        'week_items' => [
            'tuesday' => ['breakfast' => 'Upma'],
        ],
    ])->assertOk();

    expect($menu->items()->count())->toBe(1);
    expect($menu->items()->where('day', 'tuesday')->where('meal_time', 'breakfast')->first()->item_name)->toBe('Upma');
});

it('syncs inline special items through the api on update', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id, 'title' => 'API Special Sync']);

    $this->putJson(route('api.foodmenu.update', $menu->public_id), [
        'id' => $menu->public_id,
        'pg_id' => $pg->id,
        'menu_type' => 'special',
        'title' => 'API Special Sync',
        'special_date' => '2026-12-28',
        'special_items' => [
            'breakfast' => ['Tea', 'Biscuits'],
        ],
    ])->assertOk();

    $breakfast = $menu->items()->whereNull('day')->where('meal_time', 'breakfast')->orderBy('sort_order')->get();
    expect($breakfast->count())->toBe(2);
    expect($breakfast->pluck('item_name')->all())->toBe(['Tea', 'Biscuits']);
});

it('creates a special menu with multiple items per meal through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();

    $this->postJson(route('api.foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'special',
        'title' => 'Multiple Items Special',
        'special_date' => '2026-12-26',
        'special_items' => [
            'breakfast' => ['Dosa', 'Idli', 'Chutney'],
            'lunch' => ['Curd Rice', 'Veg Biryani'],
            'dinner' => [],
        ],
    ])->assertCreated();

    $menu = FoodMenu::where('title', 'Multiple Items Special')->firstOrFail();
    $breakfast = $menu->items()->whereNull('day')->where('meal_time', 'breakfast')->orderBy('sort_order')->get();

    expect($menu->items()->count())->toBe(5);
    expect($breakfast->pluck('item_name')->all())->toBe(['Dosa', 'Idli', 'Chutney']);
    expect($menu->items()->whereNull('day')->where('meal_time', 'dinner')->count())->toBe(0);
});

it('creates a weekly menu with multiple items per meal through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();

    $this->postJson(route('api.foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'Multi Items Weekly',
        'week_start_date' => '2026-09-21',
        'week_items' => [
            'monday' => [
                'breakfast' => ['Poha'],
                'lunch' => ['Samosa', 'Cutlet'],
                'dinner' => ['Dal Tadka'],
            ],
        ],
    ])->assertCreated();

    $menu = FoodMenu::where('title', 'Multi Items Weekly')->firstOrFail();
    $lunch = $menu->items()->where('day', 'monday')->where('meal_time', 'lunch')->orderBy('sort_order')->get();

    expect($menu->items()->count())->toBe(4);
    expect($lunch->pluck('item_name')->all())->toBe(['Samosa', 'Cutlet']);
});

it('keeps item descriptions when updating items by id through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id, 'title' => 'Descriptions']);
    $itemA = $menu->items()->create([
        'day' => 'monday',
        'meal_time' => 'breakfast',
        'item_name' => 'Dosa',
        'description' => 'With chutney',
        'sort_order' => 0,
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);

    $this->putJson(route('api.foodmenu.update', $menu->public_id), [
        'id' => $menu->public_id,
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'Descriptions',
        'week_start_date' => '2026-09-28',
        'week_items' => [
            'monday' => [
                'breakfast' => [
                    ['id' => $itemA->id, 'item_name' => 'Masala Dosa', 'description' => 'With sambar'],
                    'Idli',
                ],
            ],
        ],
    ])->assertOk();

    $breakfast = $menu->items()->where('day', 'monday')->where('meal_time', 'breakfast')->orderBy('sort_order')->get();
    expect($breakfast->count())->toBe(2);
    expect($breakfast[0]->item_name)->toBe('Masala Dosa');
    expect($breakfast[0]->description)->toBe('With sambar');
    expect($breakfast[0]->id)->toBe($itemA->id);
});

it('creates a special day food menu with multiple items', function () {
    $pg = PgManagement::factory()->create();

    $this->postJson(route('foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'special',
        'title' => 'Multi Special',
        'special_date' => '2026-10-01',
        'status' => 'active',
        'special_items' => [
            'breakfast' => ['Dosa', 'Idli'],
            'dinner' => ['Malpua'],
        ],
    ])->assertOk()->assertJson(['status_code' => 200]);

    $menu = FoodMenu::where('title', 'Multi Special')->firstOrFail();
    expect($menu->items()->count())->toBe(3);
    expect($menu->items()->whereNull('day')->where('meal_time', 'breakfast')->pluck('item_name')->all())->toBe(['Dosa', 'Idli']);
});

it('stores a weekly menu with multiple items and descriptions through the web', function () {
    $pg = PgManagement::factory()->create();

    $this->postJson(route('foodmenu.store'), [
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'Weekly Multi Web',
        'week_start_date' => '2026-09-28',
        'status' => 'active',
        'week_items' => [
            'monday' => [
                'breakfast' => [
                    ['item_name' => 'Idli', 'description' => 'With chutney'],
                    ['item_name' => 'Poha'],
                ],
            ],
        ],
    ])->assertOk()->assertJson(['status_code' => 200]);

    $menu = FoodMenu::where('title', 'Weekly Multi Web')->firstOrFail();
    $breakfast = $menu->items()->where('day', 'monday')->where('meal_time', 'breakfast')->orderBy('sort_order')->get();

    expect($breakfast->count())->toBe(2);
    expect($breakfast[0]->item_name)->toBe('Idli');
    expect($breakfast[0]->description)->toBe('With chutney');
});

it('updates multiple items per day through the web', function () {
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create([
        'pg_id' => $pg->id,
        'user_id' => $this->user->id,
        'menu_type' => 'weekly',
        'title' => 'Web Update Multi',
    ]);
    $itemA = $menu->items()->create([
        'day' => 'monday',
        'meal_time' => 'breakfast',
        'item_name' => 'Dosa',
        'description' => 'With chutney',
        'sort_order' => 0,
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);

    $this->putJson(route('foodmenu.update', $menu->public_id), [
        'id' => $menu->public_id,
        'pg_id' => $pg->id,
        'menu_type' => 'weekly',
        'title' => 'Web Update Multi',
        'week_start_date' => '2026-10-05',
        'week_items' => [
            'monday' => [
                'breakfast' => [
                    ['id' => $itemA->id, 'item_name' => 'Masala Dosa', 'description' => 'Updated'],
                    ['item_name' => 'Idli'],
                ],
            ],
        ],
    ])->assertOk()->assertJson(['status_code' => 200]);

    $breakfast = $menu->items()->where('day', 'monday')->where('meal_time', 'breakfast')->orderBy('sort_order')->get();

    expect($breakfast->count())->toBe(2);
    expect($breakfast[0]->id)->toBe($itemA->id);
    expect($breakfast[0]->item_name)->toBe('Masala Dosa');
    expect($breakfast[0]->description)->toBe('Updated');
});

it('adds an item to a food menu through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id]);

    $this->postJson(route('api.foodmenu.items.store', $menu->public_id), [
        'food_menu_id' => $menu->id,
        'day' => 'monday',
        'meal_time' => 'lunch',
        'item_name' => 'Samosa',
        'description' => 'Evening snack',
    ])->assertCreated()
        ->assertJsonPath('data.item_name', 'Samosa')
        ->assertJsonPath('data.meal_time', 'lunch');

    $item = FoodMenuItem::where('food_menu_id', $menu->id)->firstOrFail();
    expect($item->description)->toBe('Evening snack');
});

it('deletes a food menu item through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create(['pg_id' => $pg->id, 'user_id' => $this->user->id]);
    $item = FoodMenuItem::factory()->create(['food_menu_id' => $menu->id, 'item_name' => 'To Delete']);

    $this->deleteJson(route('api.foodmenu.items.destroy', $item->public_id), [])
        ->assertNoContent();

    expect(FoodMenuItem::find($item->id))->toBeNull();
});

it('requires a day when adding an item to a weekly menu through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create([
        'pg_id' => $pg->id,
        'user_id' => $this->user->id,
        'menu_type' => 'weekly',
    ]);

    $this->postJson(route('api.foodmenu.items.store', $menu->public_id), [
        'food_menu_id' => $menu->id,
        'meal_time' => 'breakfast',
        'item_name' => 'Poha',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['day']);

    expect(FoodMenuItem::where('food_menu_id', $menu->id)->count())->toBe(0);
});

it('rejects a day when adding an item to a special menu through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create([
        'pg_id' => $pg->id,
        'user_id' => $this->user->id,
        'menu_type' => 'special',
    ]);

    $this->postJson(route('api.foodmenu.items.store', $menu->public_id), [
        'food_menu_id' => $menu->id,
        'day' => 'monday',
        'meal_time' => 'breakfast',
        'item_name' => 'Poha',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['day']);
});

it('stores a weekly menu item with a day through the api', function () {
    Sanctum::actingAs($this->user);
    $pg = PgManagement::factory()->create();
    $menu = FoodMenu::factory()->create([
        'pg_id' => $pg->id,
        'user_id' => $this->user->id,
        'menu_type' => 'weekly',
    ]);

    $this->postJson(route('api.foodmenu.items.store', $menu->public_id), [
        'food_menu_id' => $menu->id,
        'day' => 'tuesday',
        'meal_time' => 'lunch',
        'item_name' => 'Curd Rice',
    ])->assertCreated()
        ->assertJsonPath('data.day', 'tuesday');

    $item = FoodMenuItem::where('food_menu_id', $menu->id)->firstOrFail();
    expect($item->day)->toBe('tuesday');
    expect($item->meal_time)->toBe('lunch');
});

it('blocks guests from the food menu pages', function () {
    auth()->logout();

    $this->get(route('foodmenu.index'))->assertRedirect(route('login'));
});
