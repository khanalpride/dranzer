<?php

namespace App\Builders\Processors\App\Http\Resources;

use Closure;
use Illuminate\Support\Facades\File;
use App\Builders\Processors\PHPBuilderProcessor;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Builders\PHP\Laravel\Framework\App\Http\Resources\ResourcesBuilder;

/**
 * Class ResourcesProcessor
 * @package App\Builders\Processors\App\Http\Resources
 */
class ResourcesProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $resourcesDirectory = app('project-dir') . '/app/Http/Resources';

        $apiConfig = app('mutations')->for('api');

        if ($apiConfig['generate']) {
            File::ensureDirectoryExists($resourcesDirectory);

            $modules = $apiConfig['modules'];

            foreach ($modules as $module) {
                $this->buildResource($builder, $module, $resourcesDirectory);
            }
        }

        $next($builder);

        return true;
    }

    /**
     * @param ResourcesBuilder $builder
     * @param $module
     * @param $resourcesDirectory
     * @return void
     */
    protected function buildResource(ResourcesBuilder $builder, $module, $resourcesDirectory): void
    {
        $blueprint = $module['blueprint'];
        $config = $module['config'];

        $modelName = $blueprint->getName();

        if (!($config['createResourceClass'] ?? false) && !($config['createCollectionClass'] ?? false)) {
            return;
        }

        $createResource = $config['createResourceClass'] ?? false;
        $createCollectionResource = $config['createCollectionClass'] ?? false;

        if ($createResource) {
            $builder
                ->setClassDefinition("${modelName}Resource", 'App\Http\Resources', 'JsonResource')
                ->updateClassDefinition()
                ->setFilename("${modelName}Resource.php")
                ->setOutputDir($resourcesDirectory);

            $builder->use(JsonResource::class);

            if ($config['wrapper'] && $config['wrapper'] !== 'data' && is_string($config['wrapper'])) {
                $wrapPropertyBuilder = $builder->getNewPropertyBuilder('wrap');
                $wrapPropertyBuilder
                    ->makePublic()
                    ->makeStatic()
                    ->setValue($this->string($config['wrapper']))
                    ->getDocBuilder()
                    ->addVar('string');

                $builder->addPropertyBuilder($wrapPropertyBuilder);
            }

            $toArrayMethodBuilder = $builder->getNewMethodBuilder('toArray');
            $toArrayMethodBuilder
                ->addParameter($this->param('request'))
                ->addStatement(
                    $this->return(
                        $this->staticCall('parent', 'toArray', [
                            $this->var('request')
                        ])
                    )
                )
                ->getDocBuilder()
                ->addCommentLine('Transform the resource into an array.')
                ->addCommentLine()
                ->addCommentLine('@noinspection PhpMissingReturnTypeInspection')
                ->setReturnType('array');

            $builder->addMethodBuilder($toArrayMethodBuilder);

            $builder->toDisk();
            $builder->reset();
        }

        if ($createCollectionResource) {
            $builder
                ->setClassDefinition("${modelName}Collection", 'App\Http\Resources', 'ResourceCollection')
                ->updateClassDefinition()
                ->setFilename("${modelName}Collection.php")
                ->setOutputDir($resourcesDirectory);

            $builder->use(ResourceCollection::class);

            $toArrayMethodBuilder = $builder->getNewMethodBuilder('toArray');
            $toArrayMethodBuilder
                ->addParameter($this->param('request'))
                ->addStatement(
                    $this->return(
                        $this->staticCall('parent', 'toArray', [
                            $this->var('request')
                        ])
                    )
                )
                ->getDocBuilder()
                ->addCommentLine('Transform the resource collection into an array.')
                ->addCommentLine()
                ->addCommentLine('@noinspection PhpMissingReturnTypeInspection')
                ->setReturnType('array');

            $builder->addMethodBuilder($toArrayMethodBuilder);

            $builder->toDisk();

            $builder->reset();
        }
    }
}
