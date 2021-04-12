<?php

namespace App\Builders\Processors\App\Jobs;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\App\Jobs\JobsBuilder;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use App\Builders\PHP\Laravel\Framework\App\Http\Middleware\JobMiddlewareBuilder;

/**
 * Class JobsProcessor
 * @package App\Builders\Processors\App\Jobs
 */
class JobsProcessor extends PHPBuilderProcessor
{
    /**
     * @var mixed
     */
    private array $blueprints;
    /**
     * @var mixed
     */
    private array $mailables;
    /**
     * @var mixed
     */
    private array $notifications;
    /**
     * @var string
     */
    private string $projectRoot;

    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->projectRoot = app('project-dir');
        $jobsDirectory = $this->projectRoot . '/app/Jobs';

        $this->blueprints = app('mutations')->for('database')['blueprints'];
        $this->mailables = app('mutations')->for('mail')['mailables'];
        $this->notifications = app('mutations')->for('notifications')['notifications'];

        $jobs = app('mutations')->for('queues')['jobs'];

        if (count($jobs)) {
            File::ensureDirectoryExists($jobsDirectory);
        }

        foreach ($jobs as $job) {
            $this->buildJob($builder, $job);
        }

        $next($builder);

        return true;
    }

    /**
     * @param JobsBuilder $builder
     * @param $job
     */
    private function buildJob(JobsBuilder $builder, $job): void
    {
        $jobName = $job['name'] ?? null;
        $unique = $job['unique'] ?? false;
        $uniqueUntilProcessing = $job['uniqueUntilProcessing'] ?? false;
        $overrideUniqueDuration = $job['overrideUniqueDuration'] ?? false;
        $uniqueDuration = $job['uniqueFor'] ?? 120;
        $uniqueVia = $job['uniqueVia'] ?? null;
        $middleware = $job['middleware'] ?? null;
        $typeHinted = $job['typeHint'] ?? [];

        if (!$jobName) {
            return;
        }

        $builder
            ->setClassDefinition($jobName)
            ->updateClassDefinition();

        $traits = [
            'Dispatchable'       => 'Illuminate\Foundation\Bus\Dispatchable',
            'SerializesModels'   => 'Illuminate\Queue\SerializesModels',
            'InteractsWithQueue' => 'Illuminate\Queue\InteractsWithQueue',
            'Queueable'          => 'Illuminate\Bus\Queueable',
        ];

        $implements = [
            'ShouldQueue' => ShouldQueue::class,
        ];

        if ($unique) {
            $implements = array_merge($implements, [
                'ShouldBeUnique' => ShouldBeUnique::class,
            ]);
        }

        if ($uniqueUntilProcessing && !$unique) {
            $implements = array_merge($implements, [
                'ShouldBeUniqueUntilProcessing' => ShouldBeUniqueUntilProcessing::class,
            ]);
        }

        $builder->setImplements(array_keys($implements));
        collect(array_values($implements))->each(fn ($import) => $builder->use($import));

        $builder->setTraits(array_keys($traits));
        collect(array_values($traits))->each(fn ($import) => $builder->use($import));

        $typeHinted = $this->getMappedTypeHintedEntities($typeHinted);

        $this->addTypeHintedProperties($builder, $typeHinted);

        if ($overrideUniqueDuration && is_numeric($uniqueDuration)) {
            $this->addUniqueForProperty($builder, $uniqueDuration);
        }

        $this->addConstructMethod($builder, $typeHinted)
            ->addHandleMethod($builder, $typeHinted);

        if ($uniqueVia) {
            $this->addUniqueViaMethod($builder, $uniqueVia);
        }

        if ($middleware) {
            $this->addMiddlewareMethod($builder, $middleware);
            $this->createMiddleware($middleware);
        }

        $builder
            ->setFilename("$jobName.php")
            ->toDisk();

        $builder->reset();
    }

    /**
     * @param JobsBuilder $builder
     * @param array $typeHinted
     * @return JobsProcessor
     */
    private function addConstructMethod(JobsBuilder $builder, array $typeHinted): JobsProcessor
    {
        $methodBuilder = $builder->getNewMethodBuilder('__construct');

        collect($typeHinted)->filter(fn ($t) => $t['type'] === 'model')
            ->each(function ($m) use ($methodBuilder) {
                $methodBuilder->addParameter($this->param($m['var'], $m['name']));
                $methodBuilder->addStatement(
                    $this->nestedAssign($this->propFetch('this', $m['var']), $this->var($m['var']))
                );
            });

        collect($typeHinted)->filter(fn ($t) => $t['type'] === 'mailable')
            ->each(function ($m) use ($methodBuilder) {
                $methodBuilder->addParameter($this->param($m['var'], $m['name']));
                $methodBuilder->addStatement(
                    $this->nestedAssign($this->propFetch('this', $m['var']), $this->var($m['var']))
                );
            });

        collect($typeHinted)->filter(fn ($t) => $t['type'] === 'notification')
            ->each(function ($n) use ($methodBuilder) {
                $methodBuilder->addParameter($this->param($n['var'], $n['name']));
                $methodBuilder->addStatement(
                    $this->nestedAssign($this->propFetch('this', $n['var']), $this->var($n['var']))
                );
            });

        $methodBuilder
            ->getDocBuilder()
            ->addCommentLine('Create a new job instance.')
            ->setReturnType('void');

        $builder->addMethodBuilder($methodBuilder);

        return $this;
    }

    /**
     * @param JobsBuilder $builder
     * @param array $typeHinted
     */
    private function addHandleMethod(JobsBuilder $builder, array $typeHinted): void
    {
        $methodBuilder = $builder->getNewMethodBuilder('handle');

        collect($typeHinted)->filter(fn ($t) => $t['type'] === 'model')
            ->each(function ($m) use ($methodBuilder) {
                $methodBuilder->addStatement(
                    $this->nestedAssign($this->var($m['var']), $this->propFetch('this', $m['var']))
                );
            });

        collect($typeHinted)->filter(fn ($t) => $t['type'] === 'mailable')
            ->each(function ($m) use ($methodBuilder) {
                $methodBuilder->addStatement(
                    $this->nestedAssign($this->var($m['var']), $this->propFetch('this', $m['var']))
                );
            });

        collect($typeHinted)->filter(fn ($t) => $t['type'] === 'notification')
            ->each(function ($n) use ($methodBuilder) {
                $methodBuilder->addStatement(
                    $this->nestedAssign($this->var($n['var']), $this->propFetch('this', $n['var']))
                );
            });

        if (count($methodBuilder->getStatements())) {
            $methodBuilder->addStatement($this->nop())
                ->addStatement($this->comment());
        }

        $methodBuilder
            ->getDocBuilder()
            ->addCommentLine('Execute the job.')
            ->setReturnType('void');

        $builder->addMethodBuilder($methodBuilder);
    }

    /**
     * @param JobsBuilder $builder
     * @param $middleware
     */
    private function addMiddlewareMethod(JobsBuilder $builder, $middleware): void
    {
        $builder->use('App\Http\Middleware\Jobs\\' . $middleware);

        $methodBuilder = $builder->getNewMethodBuilder('middleware');

        $methodBuilder->addStatement(
            $this->return(
                $this->arr([$this->new_($middleware)])
            )
        )
            ->setReturnType('array')
            ->getDocBuilder()
            ->addCommentLine('Get the middleware the job should pass through.')
            ->setReturnType('array');

        $builder->addMethodBuilder($methodBuilder);
    }

    /**
     * @param JobsBuilder $builder
     * @param array $typeHinted
     */
    private function addTypeHintedProperties(JobsBuilder $builder, array $typeHinted): void
    {
        collect($typeHinted)->filter(fn ($t) => $t['type'] === 'model')
            ->each(function ($m) use ($builder) {
                $builder->use('App\Models\\' . $m['name']);
                $propertyBuilder = $builder->getNewPropertyBuilder($m['var']);
                $propertyBuilder
                    ->makePrivate()
                    ->getDocBuilder()
                    ->addCommentLine('App\Models\\' . $m['name'])
                    ->addVar($m['name']);

                $builder->addPropertyBuilder($propertyBuilder);
            });

        collect($typeHinted)->filter(fn ($t) => $t['type'] === 'mailable')
            ->each(function ($m) use ($builder) {
                $builder->use('App\Mail\\' . $m['name']);
                $propertyBuilder = $builder->getNewPropertyBuilder($m['var']);
                $propertyBuilder
                    ->makePrivate()
                    ->getDocBuilder()
                    ->addCommentLine('App\Mail\\' . $m['name'])
                    ->addVar($m['name']);

                $builder->addPropertyBuilder($propertyBuilder);
            });

        collect($typeHinted)->filter(fn ($t) => $t['type'] === 'notification')
            ->each(function ($n) use ($builder) {
                $originalName = $n['originalName'] ?? null;

                $name = $originalName ?: $n['name'];

                $builder->use('App\Notifications\\' . $name, $originalName ? $n['name'] : null);
                $propertyBuilder = $builder->getNewPropertyBuilder($n['var']);
                $propertyBuilder
                    ->makePrivate()
                    ->getDocBuilder()
                    ->addCommentLine('App\Notifications\\' . $name)
                    ->addVar($n['name']);

                $builder->addPropertyBuilder($propertyBuilder);
            });

    }

    /**
     * @param JobsBuilder $builder
     * @param $duration
     */
    private function addUniqueForProperty(JobsBuilder $builder, $duration): void
    {
        $propertyBuilder = $builder->getNewPropertyBuilder('uniqueFor');
        $propertyBuilder
            ->setValue((int) $duration)
            ->getDocBuilder()
            ->addCommentLine("The number of seconds after which the job's unique lock will be released.")
            ->addVar('int');

        $builder->addPropertyBuilder($propertyBuilder);
    }

    /**
     * @param JobsBuilder $builder
     * @param $via
     */
    private function addUniqueViaMethod(JobsBuilder $builder, $via): void
    {
        $builder->use(Cache::class);
        $builder->use(Repository::class, 'CacheRepository');

        $methodBuilder = $builder->getNewMethodBuilder('uniqueVia');

        $methodBuilder
            ->addStatement(
                $this->return(
                    $this->staticCall('Cache', 'driver', [$this->string($via)])
                )
            )
            ->setReturnType('CacheRepository')
            ->getDocBuilder()
            ->addCommentLine('Get the cache driver for the unique job lock.')
            ->setReturnType('CacheRepository');

        $builder->addMethodBuilder($methodBuilder);
    }

    /**
     * @param $middleware
     */
    private function createMiddleware($middleware): void
    {
        $middleware = Str::studly($middleware);

        $jobsMiddlewareDir = $this->projectRoot . '/app/Http/Middleware/Jobs';

        File::ensureDirectoryExists($jobsMiddlewareDir);

        $builder = new JobMiddlewareBuilder();

        $builder
            ->setName($middleware)
            ->setOutputDir($jobsMiddlewareDir)
            ->build();
    }

    /**
     * @param array $typeHintedEntities
     * @return array
     */
    private function getMappedTypeHintedEntities(array $typeHintedEntities): array
    {
        // TODO: Refactor.
        $typeHinted = collect($typeHintedEntities)
            ->map(function ($h) {
                $type = $h['type'];
                $nameKey = 'name';

                if ($type === 'model') {
                    $container = $this->blueprints;
                    $nameKey = 'modelName';
                } else if ($type === 'mailable') {
                    $container = $this->mailables;
                } else {
                    $container = $this->notifications;
                }

                return [
                    'id'        => $h['id'],
                    'type'      => $type,
                    'name'      => $h['name'],
                    'container' => $container,
                    'nameKey'   => $nameKey
                ];
            })
            ->map(static function ($h) {
                $entity = collect($h['container'])
                    ->first(static function ($e) use ($h) {
                        return $e instanceof Blueprint ? ($e->getId() === $h['id']) : ($e['id'] === $h['id']);
                    });
                return [
                    'entity'  => $entity,
                    'type'    => $h['type'],
                    'nameKey' => $h['nameKey']
                ];
            })
            ->map(static function ($e) {
                if ($e['entity'] instanceof Blueprint) {
                    return [
                        'name' => $e['entity']->getName(),
                        'type' => $e['type']
                    ];
                }

                return [
                    'name' => $e['entity'][$e['nameKey']] ?? null,
                    'type' => $e['type']
                ];
            })
            ->filter(fn ($e) => $e)
            ->toArray();

        $mapped = [];

        foreach ($typeHinted as $hintedEntity) {
            $entityName = $hintedEntity['name'];
            $entityType = $hintedEntity['type'];

            $hasMailableWithSameName = false;

            if ($entityType === 'notification') {
                $mailable = collect($typeHinted)->first(fn ($h) => $h['name'] === $entityName && $h['type'] === 'mailable');
                $hasMailableWithSameName = $mailable && collect($this->mailables)->first(fn ($m) => $m['name'] === $entityName) !== null;
                if ($hasMailableWithSameName) {
                    $entityName .= 'Notification';
                }
            }

            $mapped[] = [
                'name'           => $entityName,
                'type'           => $entityType,
                'singularSnaked' => Str::snake(Str::singular($entityName)),
                'var'            => lcfirst(Str::singular($entityName)),
                'originalName'   => $hasMailableWithSameName ? $hintedEntity['name'] : null,
            ];
        }

        return collect($mapped)->sort(function ($a, $b) {
            $aLen = strlen($a['name']);
            $bLen = strlen($b['name']);
            return $aLen - $bLen;
        })->toArray();
    }
}
