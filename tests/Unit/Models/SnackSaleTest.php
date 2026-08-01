<?php

namespace Tests\Unit\Models;

use App\Models\SnackSale;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class SnackSaleTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(SnackSale::class);
    }

    public function test_computes_total_as_price_times_quantity(): void
    {
        $sale = SnackSale::factory()->create(['price' => 2.5, 'quantity' => 4]);

        $this->assertSame(10.0, $sale->total);
    }
}
