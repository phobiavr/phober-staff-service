<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;
use Phobiavr\PhoberLaravelCommon\IdempotencyKey;
use Phobiavr\PhoberLaravelCommon\Jobs\HandleSessionSchedule;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class SessionEndpointsTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Session::class, Employee::class, IdempotencyKey::class);
    }

    public function test_rejects_session_routes_without_a_valid_auth_server_token(): void
    {
        $this->withToken('bad-token')->getJson('/sessions')->assertStatus(401);
    }

    public function test_lists_active_and_queued_sessions(): void
    {
        $this->authorizeAuthServer();

        Session::factory()->create();
        Session::factory()->active()->create();
        Session::factory()->finished()->create();

        $this->withToken('token')->getJson('/sessions')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_creates_a_queued_session_and_dispatches_the_schedule_job_to_the_device_queue(): void
    {
        Queue::fake();
        $this->authorizeAuthServer();
        Http::fake(['http://device-service/price' => Http::response(['price' => 30])]);

        $employee = Employee::factory()->create();

        $response = $this->withToken('token')->postJson('/sessions', [
            'instance_id' => 4,
            'serviced_by' => $employee->id,
            'time' => 'MIN_30',
        ]);

        $response->assertOk()->assertJsonPath('status', SessionStatusEnum::QUEUE->value);

        Queue::assertPushedOn('device', HandleSessionSchedule::class);
    }

    public function test_validates_session_creation_input(): void
    {
        $this->authorizeAuthServer();

        $this->withToken('token')->postJson('/sessions', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['instance_id', 'serviced_by', 'time']);
    }

    public function test_replays_the_same_response_for_a_repeated_idempotency_key_on_session_creation(): void
    {
        Queue::fake();
        $this->authorizeAuthServer();
        Http::fake(['http://device-service/price' => Http::response(['price' => 15])]);

        $employee = Employee::factory()->create();
        $payload = ['instance_id' => 1, 'serviced_by' => $employee->id, 'time' => 'MIN_15'];

        $first = $this->withToken('token')
            ->withHeaders(['Idempotency-Key' => 'abc-123'])
            ->postJson('/sessions', $payload);

        $first->assertOk();

        $second = $this->withToken('token')
            ->withHeaders(['Idempotency-Key' => 'abc-123'])
            ->postJson('/sessions', $payload);

        $second->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJson($first->json());

        $this->assertSame(1, Session::count());
    }

    public function test_rejects_a_reused_idempotency_key_with_a_different_payload(): void
    {
        Queue::fake();
        $this->authorizeAuthServer();
        Http::fake(['http://device-service/price' => Http::response(['price' => 15])]);

        $employee = Employee::factory()->create();

        $this->withToken('token')
            ->withHeaders(['Idempotency-Key' => 'reuse-me'])
            ->postJson('/sessions', ['instance_id' => 1, 'serviced_by' => $employee->id, 'time' => 'MIN_15'])
            ->assertOk();

        $this->withToken('token')
            ->withHeaders(['Idempotency-Key' => 'reuse-me'])
            ->postJson('/sessions', ['instance_id' => 2, 'serviced_by' => $employee->id, 'time' => 'MIN_30'])
            ->assertStatus(409);
    }

    public function test_cancels_a_queued_session(): void
    {
        Queue::fake();
        $this->authorizeAuthServer();
        $session = Session::factory()->create();

        $this->withToken('token')->deleteJson("/sessions/{$session->id}")->assertNoContent();

        $this->assertSame(SessionStatusEnum::CANCELED->value, $session->fresh()->status);
    }

    public function test_starts_a_queued_session(): void
    {
        Queue::fake();
        $this->authorizeAuthServer();
        $session = Session::factory()->create();

        $this->withToken('token')->putJson("/sessions/{$session->id}/start")
            ->assertOk()
            ->assertJsonPath('status', SessionStatusEnum::ACTIVE->value);
    }

    public function test_finishes_an_active_session(): void
    {
        Queue::fake();
        $this->authorizeAuthServer();
        $session = Session::factory()->active()->create();

        $this->withToken('token')->putJson("/sessions/{$session->id}/finish")->assertNoContent();

        $this->assertSame(SessionStatusEnum::FINISHED->value, $session->fresh()->status);
    }

    public function test_requires_the_manage_discount_permission_to_set_a_discount(): void
    {
        $this->authorizeAuthServer(permissions: []);
        $session = Session::factory()->active()->create();

        $this->withToken('token')->putJson("/sessions/{$session->id}/discount", ['discount' => 20])
            ->assertStatus(403);
    }

    public function test_sets_a_discount_when_the_user_has_the_manage_discount_permission(): void
    {
        $this->authorizeAuthServer(permissions: ['manage_discount']);
        $session = Session::factory()->active()->create(['price' => 100]);

        $this->withToken('token')->putJson("/sessions/{$session->id}/discount", ['discount' => 3])
            ->assertNoContent();

        $this->assertSame(3.0, (float) $session->fresh()->discount);
    }

    public function test_shows_a_session_via_the_private_service_to_service_route(): void
    {
        $session = Session::factory()->create();

        $this->withHeaders(['X-Service-Secret' => config('service.secret')])
            ->getJson("/sessions/{$session->id}")
            ->assertOk()
            ->assertJsonPath('id', $session->id);
    }

    public function test_rejects_the_private_session_route_without_the_service_secret(): void
    {
        $session = Session::factory()->create();

        $this->getJson("/sessions/{$session->id}")->assertStatus(401);
    }
}
