<?php

namespace App\Builders\PHP;

/**
 * Class JSONBuilder
 * @package App\Builders\PHP
 */
class JSONBuilder
{
    /**
     * @var array
     */
    private array $keyValueMap = [];

    /**
     * @return false|string|string[]
     *
     * @noinspection JsonEncodingApiUsageInspection
     */
    public function build()
    {
        return str_replace("\/", '/', json_encode($this->keyValueMap, JSON_PRETTY_PRINT));
    }

    /**
     * @param $keyValueMap
     * @return JSONBuilder
     */
    public function raw($keyValueMap): JSONBuilder
    {
        $this->keyValueMap = $keyValueMap;

        return $this;
    }
}
