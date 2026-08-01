<?php

namespace Tests\Unit\Services;

use App\Events\SessionCanceled;
use App\Events\SessionCreated;
use App\Events\SessionFinished;
use App\Events\SessionStarted;
use App\Http\Requests\Session\StoreRequest;
use App\Models\Employee;
use App\Models\Session;
use App\Services\SessionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class SessionServiceTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Session::class, Employee::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeStoreRequest(array $data): StoreRequest
    {
        $request = StoreRequest::create('/sessions', 'POST', $data);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();

        return $request;
    }

    public function test_prices_a_session_via_device_service_and_creates_a_queued_session_by_default(): void
    {
        Event::fake();
        Http::fake(['http://device-service/price' => Http::response(['price' => 42.5])]);

        $employee = Employee::factory()->create();

        $session = app(SessionService::class)->create($this->makeStoreRequest([
            'instance_id' => 7,
            'serviced_by' => $employee->id,
            'time' => 'MIN_30',
        ]));

        $this->assertInstanceOf(Session::class, $session);
        $this->assertSame(SessionStatusEnum::QUEUE->value, $session->status);
        $this->assertNull($session->started_at);
        $this->assertSame(30, $session->time);
        $this->assertSame(42.5, (float) $session->price);

        Event::assertDispatched(SessionCreated::class);
    }

    public function test_starts_a_session_immediately_as_active_when_schedule_is_requested(): void
    {
        Event::fake();
        Http::fake(['http://device-service/price' => Http::response(['price' => 20])]);

        $employee = Employee::factory()->create();

        $session = app(SessionService::class)->create($this->makeStoreRequest([
            'instance_id' => 3,
            'serviced_by' => $employee->id,
            'time' => 'MIN_15',
            'schedule' => true,
        ]));

        $this->assertSame(SessionStatusEnum::ACTIVE->value, $session->status);
        $this->assertNotNull($session->started_at);
    }

    public function test_picks_the_morning_tariff_before_noon_and_evening_after_noon(): void
    {
        Http::fake(['http://device-service/price' => Http::response(['price' => 10])]);
        $employee = Employee::factory()->create();

        Carbon::setTestNow(now()->setTime(9, 0));
        app(SessionService::class)->create($this->makeStoreRequest([
            'instance_id' => 1, 'serviced_by' => $employee->id, 'time' => 'MIN_15',
        ]));
        Http::assertSent(fn ($request) => $request['tariff'] === 'MORNING');

        Carbon::setTestNow(now()->setTime(18, 0));
        app(SessionService::class)->create($this->makeStoreRequest([
            'instance_id' => 1, 'serviced_by' => $employee->id, 'time' => 'MIN_15',
        ]));
        Http::assertSent(fn ($request) => $request['tariff'] === 'EVENING');
    }

    #[Group('slow')]
    public function test_returns_a_503_when_device_service_is_unreachable(): void
    {
        Http::fake(['http://device-service/price' => fn () => throw new ConnectionException('refused')]);
        $employee = Employee::factory()->create();

        $call = fn () => app(SessionService::class)->create($this->makeStoreRequest([
            'instance_id' => 1, 'serviced_by' => $employee->id, 'time' => 'MIN_15',
        ]));

        $this->expectException(HttpResponseException::class);

        try {
            $call();
        } catch (HttpResponseException $e) {
            $this->assertSame(503, $e->getResponse()->getStatusCode());

            throw $e;
        }
    }

    public function test_passes_through_the_device_service_error_body_and_status_when_pricing_fails(): void
    {
        Http::fake(['http://device-service/price' => Http::response(['message' => 'No tariff plan'], 422)]);
        $employee = Employee::factory()->create();

        $call = fn () => app(SessionService::class)->create($this->makeStoreRequest([
            'instance_id' => 1, 'serviced_by' => $employee->id, 'time' => 'MIN_15',
        ]));

        try {
            $call();
            $this->fail('Expected HttpResponseException was not thrown.');
        } catch (HttpResponseException $e) {
            $this->assertSame(422, $e->getResponse()->getStatusCode());
            $this->assertSame(['message' => 'No tariff plan'], $e->getResponse()->getData(true));
        }
    }

    public function test_only_cancels_sessions_that_are_queued_or_active(): void
    {
        Event::fake();
        $finished = Session::factory()->finished()->create();

        try {
            app(SessionService::class)->cancel($finished->id);
            $this->fail('Expected ModelNotFoundException was not thrown.');
        } catch (ModelNotFoundException) {
            // expected
        }

        $queued = Session::factory()->create();
        $canceled = app(SessionService::class)->cancel($queued->id);

        $this->assertSame(SessionStatusEnum::CANCELED->value, $canceled->status);
        Event::assertDispatched(SessionCanceled::class);
    }

    public function test_only_starts_sessions_that_are_currently_queued(): void
    {
        Event::fake();
        $active = Session::factory()->active()->create();

        try {
            app(SessionService::class)->start($active->id);
            $this->fail('Expected ModelNotFoundException was not thrown.');
        } catch (ModelNotFoundException) {
            // expected
        }

        $queued = Session::factory()->create();
        $started = app(SessionService::class)->start($queued->id);

        $this->assertSame(SessionStatusEnum::ACTIVE->value, $started->status);
        $this->assertNotNull($started->started_at);
        Event::assertDispatched(SessionStarted::class);
    }

    public function test_only_finishes_sessions_that_are_currently_active(): void
    {
        Event::fake();
        $queued = Session::factory()->create();

        try {
            app(SessionService::class)->finish($queued->id);
            $this->fail('Expected ModelNotFoundException was not thrown.');
        } catch (ModelNotFoundException) {
            // expected
        }

        $active = Session::factory()->active()->create();
        $finished = app(SessionService::class)->finish($active->id);

        $this->assertSame(SessionStatusEnum::FINISHED->value, $finished->status);
        Event::assertDispatched(SessionFinished::class);
    }

    public function test_only_allows_discounts_on_active_or_finished_sessions(): void
    {
        $queued = Session::factory()->create();

        try {
            app(SessionService::class)->setDiscount($queued->id, 2);
            $this->fail('Expected ModelNotFoundException was not thrown.');
        } catch (ModelNotFoundException) {
            // expected
        }

        $active = Session::factory()->active()->create(['price' => 100]);
        $updated = app(SessionService::class)->setDiscount($active->id, 2);

        $this->assertSame(2.0, $updated->discount);
        $this->assertSame(98.0, $updated->end_price);
    }

    public function test_marks_an_elapsed_active_session_as_finished_in_the_today_listing_without_persisting_it(): void
    {
        $session = Session::factory()->create([
            'status' => SessionStatusEnum::ACTIVE->value,
            'started_at' => now()->subHour(),
            'time' => 15,
        ]);

        $today = app(SessionService::class)->today();

        $this->assertSame(SessionStatusEnum::FINISHED->value, $today->first()->status);
        $this->assertSame(SessionStatusEnum::ACTIVE->value, $session->fresh()->status);
    }

    public function test_active_returns_only_queued_and_active_sessions(): void
    {
        Session::factory()->create();
        Session::factory()->active()->create();
        Session::factory()->finished()->create();
        Session::factory()->canceled()->create();

        $this->assertCount(2, app(SessionService::class)->active());
    }
}
