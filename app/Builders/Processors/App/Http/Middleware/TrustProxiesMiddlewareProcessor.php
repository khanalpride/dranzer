<?php

namespace App\Builders\Processors\App\Http\Middleware;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\App\Http\Middleware\TrustProxiesBuilder;

/**
 * Class TrustProxiesMiddlewareProcessor
 * @package App\Builders\Processors\App\Http\Middleware
 */
class TrustProxiesMiddlewareProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $trustProxyConfig = app('mutations')->for('request') ?? [];

        $headers = $trustProxyConfig['headers'] ?? [];
        $proxies = $trustProxyConfig['proxies'] ?? [];

        $this->processHeaders($headers, $builder)
            ->processProxies($proxies, $builder);

        $next($builder);

        return true;
    }

    /**
     * @param array $proxies
     * @param TrustProxiesBuilder $builder
     * @return void
     */
    private function processProxies(array $proxies, TrustProxiesBuilder $builder): void
    {
        $builder
            ->setProxies(
                collect($proxies)
                    ->map(
                        fn ($proxy) => $this->string($proxy)
                    )
                    ->toArray()
            );

    }

    /**
     * @param array $headers
     * @param TrustProxiesBuilder $builder
     * @return TrustProxiesMiddlewareProcessor
     */
    private function processHeaders(array $headers, TrustProxiesBuilder $builder): TrustProxiesMiddlewareProcessor
    {
        $builder
            ->setHeaders(
                collect($headers)
                    ->map(
                        static fn ($header) => "Request::$header"
                    )
                    ->toArray()
            );

        return $this;
    }
}
