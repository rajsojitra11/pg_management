<?php

namespace Modules\Email\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Email\Models\EmailConfig;
use Modules\Email\Models\EmailTemplate;
use Modules\PgManagement\Models\PgManagement;
use Modules\User\Models\User;
use Tests\TestCase;

class EmailTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = [
            'email-list',
            'email-create',
            'email-edit',
            'email-delete',
        ];
        $this->createRoleWithPermissions('test-email-role', $permissions, $this->user);

        $this->actingAs($this->user);
    }

    protected function validConfigData(array $overrides = []): array
    {
        $pg = PgManagement::factory()->create();

        return array_merge([
            'pg_id' => $pg->id,
            'sender_email' => 'owner@example.com',
            'sender_name' => 'Owner',
            'subject_prefix' => '[PG]',
            'status' => 'active',
        ], $overrides);
    }

    // ── Config ───────────────────────────────────────────────────────────

    public function test_can_create_email_config(): void
    {
        $data = $this->validConfigData();

        $response = $this->postJson(route('email.config.store'), $data);
        $response->assertOk();

        $this->assertDatabaseHas('email_configs', [
            'pg_id' => $data['pg_id'],
            'sender_email' => 'owner@example.com',
            'status' => 'active',
        ]);
    }

    public function test_config_requires_existing_pg(): void
    {
        $response = $this->postJson(route('email.config.store'), $this->validConfigData([
            'pg_id' => 999999,
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pg_id');
    }

    public function test_config_rejects_soft_deleted_pg(): void
    {
        $pg = PgManagement::factory()->create();
        $pg->delete();

        $response = $this->postJson(route('email.config.store'), $this->validConfigData([
            'pg_id' => $pg->id,
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pg_id');
    }

    public function test_config_requires_sender_email(): void
    {
        $data = $this->validConfigData();
        unset($data['sender_email']);

        $response = $this->postJson(route('email.config.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sender_email');
    }

    public function test_config_requires_valid_status(): void
    {
        $response = $this->postJson(route('email.config.store'), $this->validConfigData([
            'status' => 'pending',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_can_update_email_config(): void
    {
        $config = EmailConfig::factory()->create(['pg_id' => PgManagement::factory()->create()->id]);

        $response = $this->putJson(route('email.config.update', $config->id), [
            'pg_id' => $config->pg_id,
            'sender_email' => 'new-owner@example.com',
            'sender_name' => 'New Owner',
            'status' => 'inactive',
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('email_configs', [
            'id' => $config->id,
            'sender_email' => 'new-owner@example.com',
            'status' => 'inactive',
        ]);
    }

    public function test_can_delete_email_config(): void
    {
        $config = EmailConfig::factory()->create(['pg_id' => PgManagement::factory()->create()->id]);

        $response = $this->deleteJson(route('email.config.destroy', $config->id));
        $response->assertOk();

        $this->assertSoftDeleted('email_configs', ['id' => $config->id]);
    }

    // ── Template ─────────────────────────────────────────────────────────

    public function test_can_save_email_template(): void
    {
        $response = $this->postJson(route('email.template.save'), [
            'subject' => 'Rent Reminder',
            'body' => '<p>Hello {tenant_name}</p>',
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('email_templates', [
            'name' => 'rent_reminder',
            'subject' => 'Rent Reminder',
            'is_default' => true,
        ]);
    }

    public function test_save_template_updates_existing(): void
    {
        EmailTemplate::factory()->create(['name' => 'rent_reminder', 'is_default' => true]);

        $this->postJson(route('email.template.save'), [
            'subject' => 'Updated Subject',
            'body' => '<p>Updated body</p>',
        ])->assertOk();

        $this->assertDatabaseHas('email_templates', [
            'name' => 'rent_reminder',
            'subject' => 'Updated Subject',
        ]);
    }

    public function test_template_requires_subject_and_body(): void
    {
        $response = $this->postJson(route('email.template.save'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject', 'body']);
    }
}
