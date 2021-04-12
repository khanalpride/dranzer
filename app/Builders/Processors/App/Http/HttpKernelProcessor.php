<?php

namespace App\Builders\Processors\App\Http;

use Closure;
use App\Http\Middleware\TrimStrings;
use App\Builders\Processors\PHPBuilderProcessor;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use App\Builders\PHP\Laravel\Framework\App\Http\KernelBuilder;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;

/**
 * Class HttpKernelProcessor
 * @package App\Builders\Processors\App\Http
 */
class HttpKernelProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this
            ->processDefaultMiddlewares($builder)
            ->processSentryMiddleware($builder)
            ->processCookieConsentMiddleware($builder)
            ->processAPIMiddleware($builder);

        $next($builder);

        return true;
    }

    /**
     * @param KernelBuilder $builder
     * @return void
     */
    private function processAPIMiddleware(KernelBuilder $builder): void
    {
        $apiConfig = app('mutations')->for('api');

        $generateAPI = $apiConfig['generate'];

        if (!$generateAPI) {
            return;
        }

        $sanctumAuth = $apiConfig['sanctumAuth'] ?? true;

        if ($sanctumAuth) {
            $builder
                ->use('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful')
                ->setApiMiddlewareGroups(
                    [
                        $this->const('EnsureFrontendRequestsAreStateful::class'),
                    ]
                );
        }

    }

    /**
     * @param KernelBuilder $builder
     * @return HttpKernelProcessor
     */
    private function processCookieConsentMiddleware(KernelBuilder $builder): HttpKernelProcessor
    {
        $cookieConsent = app('mutations')->for('compliance')['cookieConsent'];

        if ($cookieConsent['install'] && ($cookieConsent['middleware'] ?? true)) {
            $builder
                ->addMiddleware($this->const('\Spatie\CookieConsent\CookieConsentMiddleware::class'));
        }

        return $this;
    }

    /**
     * @param KernelBuilder $builder
     * @return HttpKernelProcessor
     */
    private function processSentryMiddleware(KernelBuilder $builder): HttpKernelProcessor
    {
        $exceptionsConfig = app('mutations')->for('exceptions');
        $sentryConfig = $exceptionsConfig['sentry'];

        $attachUserId = $sentryConfig['attachUserId'] ?? true;
        $attachUserEmail = $sentryConfig['attachUserEmail'] ?? true;

        if ($sentryConfig['enabled'] && ($attachUserId || $attachUserEmail)) {
            $builder
                ->use('\App\Http\Middleware\SentryContext')
                ->addMiddleware($this->const('SentryContext::class'));
        }

        return $this;
    }

    /**
     * @param KernelBuilder $builder
     * @return HttpKernelProcessor
     */
    private function processDefaultMiddlewares(KernelBuilder $builder): HttpKernelProcessor
    {
        $middlewares = app('mutations')->for('middlewares');

        $convertEmptyStringsToNull = $middlewares['convertEmptyStringsToNull'] ?? true;
        $trimStrings = $middlewares['trimStrings'] ?? true;
        $validatePostSize = $middlewares['validatePostSize'] ?? true;

        if ($convertEmptyStringsToNull) {
            $builder
                ->use(ConvertEmptyStringsToNull::class)
                ->addMiddleware(
                    $this->const("ConvertEmptyStringsToNull::class")
                );
        }

        if ($trimStrings) {
            $builder
                ->use(TrimStrings::class)
                ->addMiddleware($this->const("TrimStrings::class"));
        }

        if ($validatePostSize) {
            $builder
                ->use(ValidatePostSize::class)
                ->addMiddleware($this->const("ValidatePostSize::class"));
        }

        return $this;
    }
}
