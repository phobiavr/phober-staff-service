<?php

namespace App\Services;

use App\Http\Requests\Session\StoreRequest;
use App\Models\Session;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Response;
use Phobiavr\PhoberLaravelCommon\Clients\DeviceClient;
use Phobiavr\PhoberLaravelCommon\Enums\ScheduleEnum;
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
                    now()->isAfter(Carbon::parse($session->created_at)->addMinutes((int) $session->time))
                ) {
                    $session->status = SessionStatusEnum::FINISHED->value;
                }
            });
    }

    public function active(): Collection {
        return Session::with(['servicedBy', 'invoice'])
            ->whereIn('status', [SessionStatusEnum::ACTIVE->value, SessionStatusEnum::QUEUE->value])
            ->whereRaw('DATE_ADD(created_at, INTERVAL `time` MINUTE) > NOW()')
            ->get();
    }

    public function create(StoreRequest $request): Session {
        $now    = new DateTime(now()->format('Y-m-d H:i:s'));
        $noon   = new DateTime($now->format('Y-m-d') . ' 12:00:00');
        $tariff = $now > $noon ? SessionTariffEnum::EVENING : SessionTariffEnum::MORNING;
        $time   = $request->time();

        $scheduleId = null;

        if ($request->isScheduled()) {
            $end = (clone $now)
                ->add(new DateInterval('PT' . $time->getMins() . 'M'));
                //TODO:: make a config for updating, its' needed for reserve before session starts
                //->add(new DateInterval('PT5M'));

            $schedule = DeviceClient::schedule(ScheduleEnum::IN_SESSION, $request->instanceId(), $now, $end);

            if ($schedule->failed()) {
                throw new HttpResponseException(Response::json($schedule->json(), $schedule->status()));
            }

            $scheduleId = $schedule->json('id');
        }

        $plan = DeviceClient::price($request->instanceId(), $tariff, $time);

        if ($plan->failed()) {
            throw new HttpResponseException(Response::json($plan->json(), $plan->status()));
        }

        $invoice = $this->invoices->findOrCreateQueued(
            $request->invoiceId(),
            $request->customerId(),
            $request->customer(),
        );

        return $invoice->sessions()->create([
            'instance_id' => $request->instanceId(),
            'schedule_id' => $scheduleId,
            'serviced_by' => $request->servicedBy(),
            'time'        => $time->getMins(),
            'price'       => $plan->json('price', 0),
            'status'      => $request->isScheduled() ? SessionStatusEnum::ACTIVE : SessionStatusEnum::QUEUE,
        ]);
    }

    public function cancel(int $id): Session {
        $session = Session::whereIn('status', [
            SessionStatusEnum::QUEUE->value,
            SessionStatusEnum::ACTIVE->value,
        ])->findOrFail($id);

        if ($session->schedule_id) {
            DeviceClient::deleteSchedule($session->schedule_id);
        }

        $session->status = SessionStatusEnum::CANCELED;
        $session->save();

        return $session;
    }

    public function start(int $id): Session {
        $session = Session::where('status', SessionStatusEnum::QUEUE->value)->findOrFail($id);

        $now = new DateTime(now()->format('Y-m-d H:i:s'));
        $end = (clone $now)
            ->add(new DateInterval('PT' . $session->time . 'M'))
            ->add(new DateInterval('PT5M'));

        $schedule = DeviceClient::schedule(ScheduleEnum::IN_SESSION, $session->instance_id, $now, $end);

        if ($schedule->failed()) {
            throw new HttpResponseException(Response::json($schedule->json(), $schedule->status()));
        }

        $session->status      = SessionStatusEnum::ACTIVE;
        $session->schedule_id = $schedule->json('id');
        $session->save();

        return $session;
    }

    public function finish(int $id): Session {
        $session = Session::where('status', SessionStatusEnum::ACTIVE->value)->findOrFail($id);

        if ($session->schedule_id) {
            DeviceClient::deleteSchedule($session->schedule_id);
        }

        $session->status = SessionStatusEnum::FINISHED;
        $session->save();

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

    public function findByScheduleId(int|string $scheduleId): Session {
        return Session::where('schedule_id', $scheduleId)->firstOrFail();
    }
}
