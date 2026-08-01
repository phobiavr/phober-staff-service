<?php

namespace Tests\Feature;

use App\Models\Snack;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class SnackEndpointsTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Snack::class);
    }

    public function test_lists_snacks(): void
    {
        $this->authorizeAuthServer();
        Snack::factory()->count(2)->create();

        $this->withToken('token')->getJson('/snacks')->assertOk()->assertJsonCount(2);
    }

    public function test_sells_a_snack_against_a_new_invoice(): void
    {
        $this->authorizeAuthServer();
        $snack = Snack::factory()->create(['stock' => 10]);

        $response = $this->withToken('token')->postJson('/snacks', [
            'snack_id' => $snack->id,
            'quantity' => 2,
        ]);

        $response->assertCreated()->assertJsonStructure(['invoice_id']);
        $this->assertSame(8, $snack->fresh()->stock);
    }

    public function test_rejects_a_snack_sale_exceeding_available_stock(): void
    {
        $this->authorizeAuthServer();
        $snack = Snack::factory()->create(['stock' => 1]);

        $this->withToken('token')->postJson('/snacks', [
            'snack_id' => $snack->id,
            'quantity' => 5,
        ])->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }
}
