<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Providers;

use App\Events\App\Http\Kernel\HttpKernelBuilding;
use App\Events\App\Exceptions\ExceptionHandlerBuilding;
use App\Listeners\App\Http\Kernel\OnHttpKernelBuilding;
use App\Events\App\Console\Kernel\ConsoleKernelBuilding;
use App\Listeners\App\Exceptions\OnExceptionHandlerBuilding;
use App\Listeners\App\Console\Kernel\OnConsoleKernelBuilding;
use App\Events\App\Http\Middleware\TrustHostsMiddlewareBuilding;
use App\Events\App\Http\Middleware\TrimStringsMiddlewareBuilding;
use App\Events\App\Http\Middleware\AuthenticateMiddlewareBuilding;
use App\Events\App\Http\Middleware\TrustProxiesMiddlewareBuilding;
use App\Events\App\Http\Middleware\EncryptCookiesMiddlewareBuilding;
use App\Listeners\App\Http\Middleware\OnTrustHostsMiddlewareBuilding;
use App\Events\App\Http\Middleware\VerifyCsrfTokenMiddlewareBuilding;
use App\Listeners\App\Http\Middleware\OnTrimStringsMiddlewareBuilding;
use App\Listeners\App\Http\Middleware\OnAuthenticateMiddlewareBuilding;
use App\Listeners\App\Http\Middleware\OnTrustProxiesMiddlewareBuilding;
use App\Listeners\App\Http\Middleware\OnEncryptCookiesMiddlewareBuilding;
use App\Listeners\App\Http\Middleware\OnVerifyCsrfTokenMiddlewareBuilding;
use App\Events\App\Http\Middleware\RedirectIfAuthenticateMiddlewareBuilding;
use App\Listeners\App\Http\Middleware\OnRedirectIfAuthenticateMiddlewareBuilding;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\App\Http\Middleware\PreventRequestsDuringMaintenanceMiddlewareBuilding;
use App\Listeners\App\Http\Middleware\OnPreventRequestsDuringMaintenanceMiddlewareBuilding;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // Console
        ConsoleKernelBuilding::class => [
            OnConsoleKernelBuilding::class,
        ],
        // Exceptions
        ExceptionHandlerBuilding::class => [
            OnExceptionHandlerBuilding::class,
        ],
        // Http Kernel
        HttpKernelBuilding::class => [
            OnHttpKernelBuilding::class,
        ],
        // Middlewares
        AuthenticateMiddlewareBuilding::class => [
            OnAuthenticateMiddlewareBuilding::class,
        ],
        EncryptCookiesMiddlewareBuilding::class => [
            OnEncryptCookiesMiddlewareBuilding::class,
        ],
        PreventRequestsDuringMaintenanceMiddlewareBuilding::class => [
            OnPreventRequestsDuringMaintenanceMiddlewareBuilding::class,
        ],
        RedirectIfAuthenticateMiddlewareBuilding::class => [
            OnRedirectIfAuthenticateMiddlewareBuilding::class,
        ],
        TrimStringsMiddlewareBuilding::class => [
            OnTrimStringsMiddlewareBuilding::class,
        ],
        TrustHostsMiddlewareBuilding::class => [
            OnTrustHostsMiddlewareBuilding::class,
        ],
        TrustProxiesMiddlewareBuilding::class => [
            OnTrustProxiesMiddlewareBuilding::class,
        ],
        VerifyCsrfTokenMiddlewareBuilding::class => [
            OnVerifyCsrfTokenMiddlewareBuilding::class,
        ]
    ];
}
