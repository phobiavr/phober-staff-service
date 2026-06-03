<?php

namespace App\Services;

use App\Events\SessionCanceled;
use App\Events\SessionCreated;
use App\Events\SessionFinished;
use App\Events\SessionStarted;
use App\Http\Requests\Session\StoreRequest;
use App\Models\Session;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Response;
use Phobiavr\PhoberLaravelCommon\Jobs\HandleSessionSchedule;
use Phobiavr\PhoberLaravelCommon\Clients\DeviceClient;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;
use Phobiavr\PhoberLaravelCommon\Enums\SessionTariffEnum;

class SessionService {
    public function __construct(private readonly InvoiceService $invoices) {
    }

    public function today(): Collection {
        return Session::with(['servicedBy', 'invoice'])
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->get()
            ->each(function (Session $session) {
                if (
                    $session->status === SessionStatusEnum::ACTIVE->value &&
                    now()->isAfter(Carbon::parse($session->started_at ?? $session->created_at)->addMinutes($session->time))
                ) {
                    $session->status = SessionStatusEnum::FINISHED->value;
                }
            });
    }

    public function active(): Collection {
        return Session::with(['servicedBy', 'invoice'])
            ->whereIn('status', [SessionStatusEnum::ACTIVE->value, SessionStatusEnum::QUEUE->value])
            ->get();
    }

    public function forTV(): Collection {
        return Session::with(['servicedBy', 'invoice'])
            ->whereIn('status', [SessionStatusEnum::ACTIVE->value, SessionStatusEnum::QUEUE->value])
            ->get();
    }

    public function create(StoreRequest $request): Session|Model {
        $now    = new DateTime(now()->format('Y-m-d H:i:s'));
        $noon   = new DateTime($now->format('Y-m-d') . ' 12:00:00');
        $tariff = $now > $noon ? SessionTariffEnum::EVENING : SessionTariffEnum::MORNING;
        $time   = $request->time();

        $startedAt = $request->isScheduled() ? $now : null;

        $plan = DeviceClient::price($request->instanceId(), $tariff, $time);

        if ($plan->failed()) {
            throw new HttpResponseException(Response::json($plan->json(), $plan->status()));
        }

        $invoice = $this->invoices->findOrCreateQueued(
            $request->invoiceId(),
            $request->customerId(),
            $request->customer(),
        );

        /** @var Session $session */
        $session = $invoice->sessions()->create([
            'instance_id' => $request->instanceId(),
            'serviced_by' => $request->servicedBy(),
            'time'        => $time->getMins(),
            'price'       => $plan->json('price', 0),
            'status'      => $request->isScheduled() ? SessionStatusEnum::ACTIVE : SessionStatusEnum::QUEUE,
            'started_at'  => $startedAt,
        ]);

        event(new SessionCreated($session, $request->isScheduled() ? 'start' : 'queue'));

        return $session;
    }

    public function cancel(int $id): Session {
        $session = Session::whereIn('status', [
            SessionStatusEnum::QUEUE->value,
            SessionStatusEnum::ACTIVE->value,
        ])->findOrFail($id);

        $session->status = SessionStatusEnum::CANCELED;
        $session->save();

        event(new SessionCanceled($session));

        return $session;
    }

    public function start(int $id): Session {
        $session = Session::where('status', SessionStatusEnum::QUEUE->value)->findOrFail($id);

        $session->status     = SessionStatusEnum::ACTIVE;
        $session->started_at = now();
        $session->save();

        event(new SessionStarted($session));

        return $session;
    }

    public function finish(int $id): Session {
        $session = Session::where('status', SessionStatusEnum::ACTIVE->value)->findOrFail($id);

        $session->status = SessionStatusEnum::FINISHED;
        $session->save();

        event(new SessionFinished($session));

        return $session;
    }

    public function setDiscount(int $id, float $discount): Session {
        $session = Session::whereIn('status', [
            SessionStatusEnum::ACTIVE->value,
            SessionStatusEnum::FINISHED->value,
        ])->findOrFail($id);

        $session->discount = $discount;
        $session->save();

        return $session;
    }
}
