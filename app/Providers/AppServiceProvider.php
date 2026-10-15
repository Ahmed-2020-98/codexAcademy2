<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\WhatsAppGateway;
use App\Enums\Role;
use App\Services\Checkout\FakePaymentGateway;
use App\Services\WhatsApp\LogGateway;
use App\Services\WhatsApp\MetaCloudGateway;
use App\Services\WhatsApp\UltraMsgGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WhatsAppGateway::class, fn () => match (config('academy.whatsapp.driver')) {
            'meta' => new MetaCloudGateway,
            'ultramsg' => new UltraMsgGateway,
            default => new LogGateway,
        });

        $this->app->bind(PaymentGateway::class, fn () => match (config('academy.payments.driver')) {
            default => new FakePaymentGateway,
        });
    }

    public function boot(): void
    {
        Carbon::setLocale('ar');

        // Super Admin has full access everywhere in the RBAC matrix.
        Gate::before(fn ($user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);
    }
}
