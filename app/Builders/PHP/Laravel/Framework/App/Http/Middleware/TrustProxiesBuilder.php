<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use Fideloper\Proxy\TrustProxies;
use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\PropertyBuilder;
use App\Builders\Processors\App\Http\Middleware\TrustProxiesMiddlewareProcessor;

/**
 * Class TrustProxiesBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Middleware
 */
class TrustProxiesBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'TrustProxies.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Middleware';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'TrustProxies',
        'extend' => 'Middleware',
    ];
    /**
     * @var array|string[]
     */
    protected array $processors = [
        TrustProxiesMiddlewareProcessor::class,
    ];
    /**
     * @var array
     */
    private array $proxies = [];
    /**
     * @var null
     */
    private $headers;
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $proxiesPropertyBuilder;
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $headersPropertyBuilder;

    /**
     * @return TrustProxiesBuilder
     */
    public function prepare(): TrustProxiesBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return TrustProxiesBuilder
     */
    private function instantiatePropertyBuilders(): TrustProxiesBuilder
    {
        return $this
            ->setProxiesPropertyBuilder($this->getNewPropertyBuilder('proxies'))
            ->setHeadersPropertyBuilder($this->getNewPropertyBuilder('headers'));
    }

    /**
     * @return TrustProxiesBuilder
     */
    protected function buildUseStatements(): TrustProxiesBuilder
    {
        $this->useIlluminateHttpRequest()
            ->useTrustProxiesMiddleware();

        return $this;
    }

    /**
     * @return TrustProxiesBuilder
     */
    private function setDefaults(): TrustProxiesBuilder
    {
        $this->setHeaders(
            [
                "Request::HEADER_X_FORWARDED_FOR",
                "Request::HEADER_X_FORWARDED_HOST",
                "Request::HEADER_X_FORWARDED_PORT",
                "Request::HEADER_X_FORWARDED_PROTO",
                "Request::HEADER_X_FORWARDED_AWS_ELB",
            ]
        )
            ->getHeadersPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The headers that should be used to detect proxies.')
            ->addVar('int');

        $this
            ->getProxiesPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The trusted proxies for this application.')
            ->addVar('array');

        return $this;
    }

    /**
     * @return $this
     */
    protected function useTrustProxiesMiddleware(): TrustProxiesBuilder
    {
        $this->use(TrustProxies::class, 'Middleware');

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return TrustProxiesBuilder
     */
    protected function buildClass(): TrustProxiesBuilder
    {
        $this->addProxiesProperty()
            ->addHeadersProperty();

        return $this;
    }

    /**
     * @return void
     */
    private function addHeadersProperty(): void
    {
        $headers = $this->getHeaders();

        $headerStmt = $this->const($headers[0]);

        $headers = array_slice($headers, 1);

        foreach ($headers as $header) {
            $headerStmt = $this->bitwiseOr($headerStmt, $this->const($header));
        }

        $this
            ->getHeadersPropertyBuilder()
            ->setValue($headerStmt);

        $this->addPropertyBuilder($this->getHeadersPropertyBuilder());
    }

    /**
     * @return TrustProxiesBuilder
     */
    private function addProxiesProperty(): TrustProxiesBuilder
    {
        $proxies = $this->arr($this->getProxies());

        $this
            ->getProxiesPropertyBuilder()
            ->setValue($proxies);

        $this->addPropertyBuilder($this->getProxiesPropertyBuilder());

        return $this;
    }

    /**
     * @return null
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param mixed $headers
     * @return TrustProxiesBuilder
     */
    public function setHeaders(array $headers): TrustProxiesBuilder
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getHeadersPropertyBuilder(): PropertyBuilder
    {
        return $this->headersPropertyBuilder;
    }

    /**
     *
     * @param PropertyBuilder $headersPropertyBuilder
     * @return TrustProxiesBuilder
     */
    public function setHeadersPropertyBuilder(PropertyBuilder $headersPropertyBuilder): TrustProxiesBuilder
    {
        $this->headersPropertyBuilder = $headersPropertyBuilder;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProxies(): array
    {
        return $this->proxies;
    }

    /**
     * @param mixed $proxies
     * @return TrustProxiesBuilder
     */
    public function setProxies(array $proxies): TrustProxiesBuilder
    {
        $this->proxies = $proxies;
        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getProxiesPropertyBuilder(): PropertyBuilder
    {
        return $this->proxiesPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $proxiesPropertyBuilder
     * @return TrustProxiesBuilder
     */
    public function setProxiesPropertyBuilder(PropertyBuilder $proxiesPropertyBuilder): TrustProxiesBuilder
    {
        $this->proxiesPropertyBuilder = $proxiesPropertyBuilder;

        return $this;
    }
}
