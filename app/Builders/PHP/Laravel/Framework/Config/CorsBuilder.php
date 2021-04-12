<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;
use App\Builders\Processors\Config\CorsProcessor;

/**
 * Class CorsBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class CorsBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        CorsProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'cors.php';
    /**
     * @var bool
     */
    private $supportsCredentials = false;

    /**
     * @return $this
     */
    public function prepare(): CorsBuilder
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildConfigArray()
            ->toDisk();
    }

    /**
     * @return CorsBuilder
     */
    private function buildConfigArray(): CorsBuilder
    {
        $this->retArr(([
            $this->getPathsKey(),
            $this->getAllowedMethodsKey(),
            $this->getAllowedOriginsKey(),
            $this->getAllowedOriginsPatternsKey(),
            $this->getAllowedHeadersKey(),
            $this->getExposedHeadersKey(),
            $this->getMaxAgeKey(),
            $this->getSupportsCredentialsKey(),
        ]));

        return $this;
    }

    /**
     * @return bool
     */
    public function getSupportsCredentials(): bool
    {
        return $this->supportsCredentials;
    }

    /**
     * @param bool $supportsCredentials
     * @return CorsBuilder
     */
    public function setSupportsCredentials(bool $supportsCredentials): CorsBuilder
    {
        $this->supportsCredentials = $supportsCredentials;

        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getPathsKey(): ArrayItem
    {
        return $this->assoc('paths', $this->arr([
            $this->string('api/*')
        ]), 'Cross-Origin Resource Sharing (CORS) Configuration', 'Here you may configure your settings for cross-origin resource sharing or "CORS". This determines what cross-origin operations may execute in web browsers. You are free to adjust these settings as needed. To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS');
    }

    /**
     * @return ArrayItem
     */
    private function getAllowedMethodsKey(): ArrayItem
    {
        return $this->assoc('allowed_methods', $this->arr([
            $this->string('*')
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getAllowedOriginsKey(): ArrayItem
    {
        return $this->assoc('allowed_origins', $this->arr([
            $this->string('*')
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getAllowedOriginsPatternsKey(): ArrayItem
    {
        return $this->assoc('allowed_origins_patterns', $this->arr([]));
    }

    /**
     * @return ArrayItem
     */
    private function getAllowedHeadersKey(): ArrayItem
    {
        return $this->assoc('allowed_headers', $this->arr([
            $this->string('*')
        ]));
    }


    /**
     * @return ArrayItem
     */
    private function getExposedHeadersKey(): ArrayItem
    {
        return $this->assoc('exposed_headers', $this->arr([]));
    }

    /**
     * @return ArrayItem
     */
    private function getMaxAgeKey(): ArrayItem
    {
        return $this->assoc('max_age', $this->int(0));
    }

    /**
     * @return ArrayItem
     */
    private function getSupportsCredentialsKey(): ArrayItem
    {
        return $this->assoc('supports_credentials', $this->const($this->getSupportsCredentials()));
    }
}
