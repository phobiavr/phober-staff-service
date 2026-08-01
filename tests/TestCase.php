<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Phobiavr\PhoberLaravelCommon\Testing\FakesAuthServer;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;
    use FakesAuthServer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFakesAuthServer();
    }
}
