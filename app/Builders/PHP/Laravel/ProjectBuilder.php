<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace App\Builders\PHP\Laravel;

use SplFileInfo;
use ReflectionClass;
use ReflectionException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Builders\Contracts\IFileBuilder;
use App\Builders\Helpers\PipelineHelpers;
use App\Services\Mutation\MutationService;
use App\Builders\Contracts\IBuildDelegator;
use App\Builders\PHP\Laravel\Preprocessors\MutationsPreprocessor;

/**
 * Class ProjectBuilder
 * @package App\Builders\PHP\Laravel
 */
class ProjectBuilder
{
    /**
     * @param string $projectId
     * @return string
     */
    public function build(string $projectId): string
    {
        $builderBaseDirectory = app_path('Builders/PHP/Laravel/Framework');

        $builderBaseNS = 'App\Builders\PHP\Laravel\Framework\\';

        $builderMap = $this->getBuilderMap($builderBaseDirectory, $builderBaseNS);

        $this->createInstances($builderMap);

        $buildDirName = Str::random();

        $projectDir = $this->createDirectories($buildDirName);

        $this->copyStaticFiles($builderBaseDirectory, $projectDir);

        $mutations = MutationService::getMutations($projectId);

        MutationsPreprocessor::preprocess($mutations, $projectDir, $projectId);

        BindingsManager::registerMutationBindings($mutations, $projectId);
        BindingsManager::registerProjectBindings($projectId, $projectDir);
        BindingsManager::registerFileSystemBindings();
        BindingsManager::registerConfigBindings();

        $staticDirectories = $this->getStaticDirectories();

        $dirConversionMap = [
            'Public_' => 'public',
        ];

        foreach ($builderMap as $builderClass => $builderPath) {
            $outputPath = $this->resolveBuilderOutputPath(
                $builderPath, $dirConversionMap, $staticDirectories, $projectDir
            );

            /**
             * Resolve the builder instance from the container.
             *
             * @more-info @method self createInstances
             */
            $builderInstance = app(substr($builderClass, 1));

            $delegator = in_array(IBuildDelegator::class, class_implements($builderInstance), true);

            if (!$delegator) {
                $builderInstance->setOutputDir($outputPath);
            }

            $builderInstance->prepare();

            PipelineHelpers::processBuilderProcessors($builderInstance)
                ->then(static function ($response) use ($delegator) {
                    // The delegator doesn't have a build method and hands-off
                    // the build to the processors which returns a boolean.
                    if ($delegator) {
                        return $response;
                    }

                    // Make sure we have the expected builder instance before
                    // calling the build method.
                    $isBuilder = in_array(IFileBuilder::class, class_implements($response), true);

                    // A processor can set the canBuild flag to false indicating
                    // that either something went wrong during processing or
                    // the pre-requisites (e.g. feature activation) are not met.
                    // In either case, the build method must not be called.
                    if ($isBuilder && $response->canBuild()) {
                        return $response->build();
                    }

                    return false;
                }) === true;
        }

        $this
            ->addStorageLinkCommand()
            ->prettify($projectDir)
            ->initGit($projectDir)
            ->createExampleEnv($projectDir)
            ->generateFreshKey($projectDir);

        return $buildDirName;
    }

    /**
     * @return ProjectBuilder
     */
    private function addStorageLinkCommand(): ProjectBuilder
    {
        ArtisanCommands::add('storage:link');

        return $this;
    }

    /**
     * @param $tempDirname
     * @return string
     */
    private function createDirectories($tempDirname): string
    {
        $outputPath = storage_path('app/generated/' . $tempDirname . '/');

        File::ensureDirectoryExists($outputPath);

        collect($this->getStaticDirectories())
            ->each(static fn ($dir) => File::ensureDirectoryExists($outputPath . $dir));

        return $outputPath;
    }

    /**
     * @param $projectDirectory
     * @return ProjectBuilder
     */
    private function createExampleEnv($projectDirectory): ProjectBuilder
    {
        shell_exec("cd $projectDirectory && cp .env .env.example");

        return $this;
    }

    /**
     *
     * @param $classMap
     */
    private function createInstances($classMap): void
    {
        // $classMap maps class => output-dir
        foreach (array_keys($classMap) as $class) {
            // Explicitly casting $class to string fixes psalm's complain:
            // Type array-key cannot be called as a class.
            app()->singletonIf($class, static fn () => new ((string) $class));
        }
    }

    /**
     * @param $buildersDirectory
     * @param $builderBaseNS
     * @return array
     */
    private function getBuilderMap($buildersDirectory, $builderBaseNS): array
    {
        $map = [];

        $conversionMap = [
            'Public' => 'Public_',
        ];

        $files = File::allFiles($buildersDirectory);

        // Only filenames with the Builder suffix are considered valid builders.
        $files = collect($files)
            ->filter(
                static fn ($file) => Str::endsWith(
                    pathinfo($file, PATHINFO_FILENAME),
                    'Builder'
                )
            );

        foreach ($files as $file) {
            $class = $this->resolveClassFromFile($file, $builderBaseNS, $conversionMap);

            try {
                $refClass = new ReflectionClass($class);
                $parentClass = $refClass->getParentClass();
                $isCustomBuilder = $this->hasCustomBuilderProperty($refClass);

                if ($parentClass) {
                    $isAbstract = $refClass->isAbstract() && $parentClass->isAbstract();
                } else {
                    $isAbstract = $refClass->isAbstract();
                }
            } catch (ReflectionException $e) {
                // TODO: Log the exception and display to the user.
                continue;
            }

            // Ignore if it's a custom builder or an abstract class.
            // A custom builder is a builder built by other builders.
            if ($isAbstract || $isCustomBuilder) {
                continue;
            }

            $map[$class] = $file->getRelativePath();
        }

        return $map;
    }

    /**
     * @param ReflectionClass $class
     * @return bool
     */
    private function hasCustomBuilderProperty(ReflectionClass $class): bool
    {
        return array_key_exists('customBuilder', $class->getStaticProperties()) && ($class->getStaticPropertyValue('customBuilder') === true);
    }

    /**
     * @param SplFileInfo $file
     * @param string $builderBaseNS
     * @param array $conversionMap
     * @return string
     */
    private function resolveClassFromFile(SplFileInfo $file, string $builderBaseNS, array $conversionMap = []): string
    {
        $filename = pathinfo($file, PATHINFO_FILENAME);

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $relativePath = $file->getRelativePath();

        $namespace = $this->pathToNamespace($relativePath);

        if (array_key_exists($namespace, $conversionMap)) {
            $namespace = $conversionMap[$namespace];
        }

        $namespace = !empty($namespace) ? $namespace . '\\' : $namespace;

        return '\\' . $builderBaseNS . $namespace . $filename;
    }

    /**
     * @param $path
     * @return string
     */
    private function pathToNamespace($path): string
    {
        return ucwords(str_replace('/', '\\', $path), '\\');
    }

    /**
     * @return string[]
     */
    private function getStaticDirectories(): array
    {
        // Even though the directories are create recursively,
        // the root directory must be listed.
        // TODO: Refactor.
        return [
            'app',
            'app/Console/Commands',
            'app/Exceptions',
            'app/Http/Controllers',
            'app/Http/Middleware',
            'app/Providers',
            'app/Models',
            'bootstrap',
            'bootstrap/cache',
            'config',
            'database/factories',
            'database/migrations',
            'database/seeders',
            'resources/lang',
            'resources/lang/en',
            'resources/js',
            'resources/views',
            'public',
            'routes',
            'storage/app',
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/testing',
            'storage/framework/views',
            'tests/Feature',
            'tests/Unit',
        ];
    }

    /**
     * @param $sourceDir
     * @param $outputDir
     */
    private function copyStaticFiles($sourceDir, $outputDir): void
    {
        $staticFiles = [
            'resources/views/welcome.blade.php' => 'resources/views/welcome.blade.php',

            'resources/lang/en/auth.php'       => 'resources/lang/en/auth.php',
            'resources/lang/en/pagination.php' => 'resources/lang/en/pagination.php',
            'resources/lang/en/passwords.php'  => 'resources/lang/en/passwords.php',
            'resources/lang/en/validation.php' => 'resources/lang/en/validation.php',

            'database/migrations/2019_08_19_000000_create_failed_jobs_table.php'
            => 'database/migrations/2019_08_19_000000_create_failed_jobs_table.php',

            'artisan'    => 'artisan',
            '.gitignore' => '.gitignore',
        ];

        $staticFiles = collect($staticFiles)
            ->map(static fn ($source, $target) => ["Static/$source" => $target])
            ->collapse()
            ->toArray();

        foreach ($this->getStaticFiles($staticFiles) as $source => $destination) {
            File::copy($sourceDir . '/' . $source, $outputDir . '/' . $destination);
        }
    }

    /**
     * @param array $additionalFiles
     * @return array
     */
    private function getStaticFiles(array $additionalFiles = []): array
    {
        return array_merge(
            [
                'Public_/.htaccess' => 'public/.htaccess',
            ], $additionalFiles
        );
    }

    /**
     * @param string $projectDir
     * @param array $conversionMap
     * @param array $staticDirectories
     * @param string $builderClassFilePath
     *
     * @return string
     */
    private function resolveBuilderOutputPath(string $builderClassFilePath, array $conversionMap, array $staticDirectories, string $projectDir): string
    {
        $directories = explode('/', $builderClassFilePath);

        $directories = array_map(
            static function ($item) use ($conversionMap, $staticDirectories) {
                if (array_key_exists($item, $conversionMap)) {
                    return $conversionMap[$item];
                }

                foreach ($staticDirectories as $dir) {
                    if (strtolower($dir) === strtolower($item)) {
                        return $dir;
                    }
                }

                return $item;
            }, $directories
        );

        return $projectDir . implode('/', $directories);
    }

    /**
     * @param $projectDirectory
     * @return void
     */
    private function generateFreshKey($projectDirectory): void
    {
        shell_exec("cd $projectDirectory && php artisan key:generate");
    }

    /**
     * @param $projectDirectory
     * @return ProjectBuilder
     */
    private function initGit($projectDirectory): ProjectBuilder
    {
        shell_exec("cd $projectDirectory && git init");

        return $this;
    }

    /**
     * @param $projectDir
     * @return ProjectBuilder
     */
    private function prettify($projectDir): ProjectBuilder
    {
        $directories = [
            'app',
            'config',
            'bootstrap',
            'database',
            'public',
            'mix',
            'resources/js',
            'routes',
        ];

        $files = [
            'webpack.mix.js',
            'tailwind.config.js',
            '.eslintrc.json',
            'server.php',
            'artisan',
        ];

        $dirPaths = collect($directories)->map(static fn ($dir) => "'" . $projectDir . $dir . '/**' . "'");

        $filePaths = collect($files)->map(static fn ($file) => "'" . $projectDir . $file . "'");

        $paths = $dirPaths->concat($filePaths)->join(' ');

        $prettifyCmd = 'prettier --single-quote --quote-props="consistent" --write ' . $paths;

        shell_exec($prettifyCmd);

        return $this;
    }
}
