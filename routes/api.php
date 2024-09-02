<?php

use App\Http\Requests\SessionStoreRequest;
use App\Http\Requests\SnackDealRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\SessionResource;
use App\Http\Resources\SnackResource;
use App\Models\Invoice;
use App\Models\Session;
use App\Models\Snack;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Shared\Clients\CrmClient;
use Shared\Clients\DeviceClient;
use Shared\Enums\InvoiceStatusEnum;
use Shared\Enums\ScheduleEnum;
use Shared\Enums\SessionStatusEnum;
use Shared\Enums\SessionTariffEnum;
use Shared\Enums\SessionTimeEnum;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

Route::middleware('otp.generate')->group(function () {
    Route::get('/discount-make', function () {
        return 'make';
    });
});

Route::middleware('otp')->group(function () {
    Route::get('/discount-submit', function () {
        return 'submit';
    });
});

Route::middleware('auth.server')->group(function () {
    Route::get('', function () {
        return Auth::guard('server')->user();
    });

    Route::get('/invoices', function () {
        $invoices = Invoice::all();

        return Response::json(InvoiceResource::collection($invoices));
    });

    Route::post('/sessions', function (SessionStoreRequest $request) {
        $now = new DateTime(now()->format('Y-m-d H:i:s'));
        $noon = new DateTime($now->format('Y-m-d') . ' 12:00:00');
        $tariff = $now > $noon ? SessionTariffEnum::EVENING : SessionTariffEnum::MORNING;
        $time = SessionTimeEnum::from($request->get('time'));
        $instanceId = $request->get('instance_id');
        $invoiceId = $request->get('invoice_id');
        $customerId = $request->get('customer_id');
        $customer = $request->get('customer', 'Quest');
        $employeeId = $request->get('serviced_by');
        $scheduleId = null;

        if ($isScheduled = $request->get('schedule', false)) {
            $end = clone $now;
            $end->add(new DateInterval('PT' . $time->getMins() . 'M'));

            //TODO:: make a config for updating, its' needed for reserve before session starts
            $end->add(new DateInterval('PT5M'));

            if (($schedule = DeviceClient::schedule(ScheduleEnum::IN_SESSION, $instanceId, $now, $end))->failed()) {
                return $schedule->json();
            }

            $scheduleId = $schedule->json('id');
        }

        if (($plan = DeviceClient::price($instanceId, $tariff, $time))->failed()) {
            return $plan->json();
        }

        if (!$invoice = Invoice::where('id', $invoiceId)->where('status', InvoiceStatusEnum::QUEUE)->first()) {
            if ($customerId && !(($customerFromService = CrmClient::customer($customerId))->failed())) {
                $customer = $customerFromService->json('full_name');
            }

            $invoice = Invoice::create([
                'customer_id' => $customerId,
                'customer'    => $customer,
                'status'      => InvoiceStatusEnum::QUEUE
            ]);
        }

        $session = $invoice->sessions()->create([
            "instance_id" => $instanceId,
            "schedule_id" => $scheduleId,
            "serviced_by" => $employeeId,
            "time"        => $time->getMins(),
            "price"       => $plan->json('price', 0),
            "status"      => $isScheduled ? SessionStatusEnum::ACTIVE : SessionStatusEnum::QUEUE
        ]);

        return Response::json(SessionResource::make($session));
    });

    Route::delete('/sessions/{id}', function ($id) {
        $session = Session::whereIn('status', [SessionStatusEnum::QUEUE->value, SessionStatusEnum::ACTIVE->value])->findOrFail($id);

        if ($session->schedule_id) {
            DeviceClient::deleteSchedule($session->schedule_id);
        }

        $session->status = SessionStatusEnum::CANCELED;
        $session->save();

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    });

    Route::put('/sessions/{id}/start', function (int $id) {
        $session = Session::where('status', SessionStatusEnum::QUEUE->value)->findOrFail($id);

        $now = new DateTime(now()->format('Y-m-d H:i:s'));
        $end = clone $now;
        $end->add(new DateInterval('PT' . $session->time . 'M'));

        $end->add(new DateInterval('PT5M'));

        if (($schedule = DeviceClient::schedule(ScheduleEnum::IN_SESSION, $session->instance_id, $now, $end))->failed()) {
            return $schedule->json();
        }

        $scheduleId = $schedule->json('id');

        $session->status = SessionStatusEnum::ACTIVE;
        $session->schedule_id = $scheduleId;
        $session->save();

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    });

    Route::put('/sessions/{id}/finish', function (int $id) {
        $session = Session::where('status', SessionStatusEnum::ACTIVE->value)->findOrFail($id);

        if ($session->schedule_id) {
            DeviceClient::deleteSchedule($session->schedule_id);
        }

        $session->status = SessionStatusEnum::FINISHED;
        $session->save();

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    });

    Route::get('/sessions/{id}/discount/{discount}', function (int $id, float $discount) {
        $session = Session::where('status', SessionStatusEnum::ACTIVE->value)->findOrFail($id);

        $session->discount = $discount;
        $session->save();

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    });

    Route::get('/snacks', function () {
        return Response::json(SnackResource::collection(Snack::all()));
    });

    Route::post('/snacks', function (SnackDealRequest $request) {
        $snackId = $request->get('snack_id');
        $quantity = $request->get('quantity');
        $invoiceId = $request->get('invoice_id');
        $customerId = $request->get('customer_id');
        $customer = $request->get('customer', 'Quest');

        if (!$invoice = Invoice::where('id', $invoiceId)->where('status', InvoiceStatusEnum::QUEUE)->first()) {
            if ($customerId && !(($customerFromService = CrmClient::customer($customerId))->failed())) {
                $customer = $customerFromService->json('full_name');
            }

            $invoice = Invoice::create([
                'customer_id' => $customerId,
                'customer'    => $customer,
                'status'      => InvoiceStatusEnum::QUEUE
            ]);
        }

        $snack = Snack::findOrFail($snackId);
        $snack->stock -= $quantity;
        $snack->save();

         $invoice->snackSales()->create([
            "snack_id" => $snackId,
            "quantity" => $quantity,
        ]);

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    });
});

Route::middleware('private')->group(function () {
    Route::get('/sessions/byScheduleId/{scheduleId}', function ($scheduleId) {
        $session = Session::where('schedule_id', $scheduleId)->firstOrFail();

        $result = [
            'serviced_by' => $session->servicedBy->full_name,
            'time'        => $session->time,
            'customer'    => $session->invoice->customer
        ];

        return Response::json($result);
    });
});

