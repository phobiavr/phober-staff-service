<?php

namespace Tests\Feature;

use App\Models\Session;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class TvEndpointsTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Session::class);
    }

    public function test_issues_a_signed_tv_url_behind_a_short_numeric_pin(): void
    {
        $this->authorizeAuthServer();

        $token = $this->withToken('token')->postJson('/tv/token')
            ->assertOk()
            ->assertJsonStructure(['pin', 'expires_at']);

        $pin = $token->json('pin');
        $this->assertMatchesRegularExpression('/^\d{4}$/', $pin);

        $this->getJson("/tv/pin/{$pin}")
            ->assertOk()
            ->assertJsonStructure(['url']);
    }

    public function test_returns_404_for_an_unknown_or_expired_pin(): void
    {
        $this->getJson('/tv/pin/0000')->assertStatus(404);
    }

    public function test_serves_the_tv_sessions_listing_through_the_resolved_signed_url(): void
    {
        $this->authorizeAuthServer();
        Session::factory()->active()->create();
        Session::factory()->create();

        $token = $this->withToken('token')->postJson('/tv/token')->json();
        $signedUrl = $this->getJson('/tv/pin/'.$token['pin'])->json('url');

        $this->getJson($signedUrl)->assertOk()->assertJsonCount(2);
    }

    public function test_rejects_an_unsigned_or_tampered_tv_sessions_url(): void
    {
        $this->getJson('/tv/sessions')->assertStatus(403);
    }
}
