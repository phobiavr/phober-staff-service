<?php

use App\Http\Requests\SessionStoreRequest;
use App\Models\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Shared\Clients\DeviceClient;
use Shared\Device\ScheduleEnum;
use Shared\Enums\SessionStatusEnum;
use Shared\Enums\SessionTariffEnum;
use Shared\Enums\SessionTimeEnum;

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

    Route::post('/session', function (SessionStoreRequest $request) {
        $now = new DateTime(now()->format('Y-m-d H:i:s'));
        $noon = new DateTime($now->format('Y-m-d') . ' 12:00:00');
        $tariff = $now > $noon ? SessionTariffEnum::EVENING : SessionTariffEnum::MORNING;
        $time = SessionTimeEnum::from($request->get('tariff'));
        $instanceId = $request->get('instance_id');
        $end = clone $now;
        $end->add(new DateInterval('PT' . $time->getMins() . 'M'));

        //TODO:: make a config for updating, its' needed for reserve before session starts
        $end->add(new DateInterval('PT5M'));

        $schedule = DeviceClient::schedule(ScheduleEnum::IN_SESSION, $instanceId, $now, $end);

        if ($schedule->failed()) {
            return $schedule->json();
        }

        $session = Session::create([
            "instance_id" => $instanceId,
            "serviced_by" => $request->get('serviced_by'),
            "time"        => $time->getMins(),
            "tariff"      => $tariff->value,
            "price"       => 15, //TODO:: update price logic,
            "status"      => SessionStatusEnum::QUEUE
        ]);

        return Response::json($session->toArray());
    });
});

