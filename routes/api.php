<?php

use App\Http\Requests\SessionStoreRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\SessionResource;
use App\Models\Invoice;
use App\Models\Session;
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
        $time = SessionTimeEnum::from($request->get('tariff'));
        $instanceId = $request->get('instance_id');
        $invoiceId = $request->get('invoice_id');
        $customer = $request->get('customer', 'Quest');
        $customerId = $request->get('customer_id');
        $employeeId = $request->get('serviced_by');
        $scheduleId = null;

        if (!($isQueue = ($request->get('queue') !== false))) {
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
            "status"      => $isQueue ? SessionStatusEnum::QUEUE : SessionStatusEnum::ACTIVE
        ]);

        return Response::json(SessionResource::make($session));
    });

    Route::delete('/sessions/{id}', function ($id) {
        $session = Session::where('status', '<>', SessionStatusEnum::CANCELED)->findOrFail($id);

        if ($session->schedule_id) {
            DeviceClient::deleteSchedule($session->schedule_id);
        }

        $session->status = SessionStatusEnum::CANCELED;
        $session->save();

        return Response::json('', ResponseFoundation::HTTP_NO_CONTENT);
    });
});

