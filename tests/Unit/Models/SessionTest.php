<?php

namespace Tests\Unit\Models;

use App\Models\Session;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Session::class);
    }

    public function test_computes_end_price_from_price_and_discount_percentage(): void
    {
        $session = Session::factory()->create(['price' => 100, 'discount' => 0]);
        $this->assertSame(100.0, $session->end_price);

        $session->discount = 3;
        $this->assertSame(97.0, $session->end_price);

        $session->discount = 5;
        $this->assertSame(95.0, $session->end_price);
    }

    public function test_treats_a_null_discount_as_zero(): void
    {
        $session = Session::factory()->create(['price' => 80]);
        $session->discount = null;

        $this->assertSame(80.0, $session->end_price);
    }
}
