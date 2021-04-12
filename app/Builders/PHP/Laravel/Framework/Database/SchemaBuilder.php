<?php

namespace App\Builders\PHP\Laravel\Framework\Database;

use App\Builders\Contracts\IBuildDelegator;
use App\Builders\Processors\App\Models\ModelsProcessor;
use App\Builders\Processors\Database\Seeders\SeederProcessor;
use App\Builders\Processors\Database\Factories\FactoryProcessor;
use App\Builders\Processors\Database\Migrations\MigrationProcessor;

/**
 * Class SchemaBuilder
 * @package App\Builders\PHP\Laravel\Framework\Database
 */
class SchemaBuilder implements IBuildDelegator
{
    // Since the SchemaBuilder (build delegator) doesn't have a build method,
    // these processors are actually responsible for building the schema.
    /**
     * @var array|string[]
     */
    protected array $processors = [
        FactoryProcessor::class,
        MigrationProcessor::class,
        SeederProcessor::class,
        ModelsProcessor::class,
    ];

    /**
     * @return $this
     */
    public function prepare(): SchemaBuilder
    {
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }
}
