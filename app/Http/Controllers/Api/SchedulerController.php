<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SchedulerController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = (string) config('academy.scheduler_secret');

        abort_if($secret === '' || ! hash_equals($secret, (string) $request->bearerToken()), 401);

        $lock = Cache::lock('academy:scheduler', 120);
        abort_unless($lock->get(), 409);

        try {
            abort_unless(Artisan::call('schedule:run', ['--no-interaction' => true]) === 0, 500);

            return response()->noContent();
        } finally {
            $lock->release();
        }
    }
}
