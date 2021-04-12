<?php

namespace App\Builders\PHP\Laravel;

use App\Builders\PHP\Laravel\Parsers\Mutations\MutationsParser;

/**
 * Class BindingsManager
 * @package App\Builders\PHP\Laravel
 */
class BindingsManager
{
    /**
     * @param $mutations
     * @param $projectId
     */
    public static function registerMutationBindings($mutations, $projectId): void
    {
        $mutationsParser = new MutationsParser;

        /**
         * Bind mutations parser.
         *
         * The mutations can be accessed using the following methods:
         *
         * @method MutationsParser all Get mutations for all modules.
         * @method MutationsParser for Get mutations for a specific module.
         *
         * Usage:
         *
         * app('mutations')->all()['database']
         * app('mutations')->for('database')
         *
         */
        app()->singletonIf('mutations', static fn () => $mutationsParser);

        // Mutations must be parsed after the parser is bound to the container
        // as some module parsers make use of the parsed modules (e.g. Database)
        $mutationsParser->parse($mutations, $projectId);

        // As long as the mutations are properly parsed, we shouldn't
        // have to access the raw mutations.
        app()->singletonIf('raw-mutations', static fn () => $mutations);
    }

    /**
     * @param $projectId
     * @param null $projectDir
     */
    public static function registerProjectBindings($projectId, $projectDir = null): void
    {
        app()->singletonIf('project-id', static fn () => $projectId);
        app()->singletonIf('project-dir', static fn () => $projectDir);
    }

    /**
     *
     */
    public static function registerFileSystemBindings(): void
    {
        app()->singletonIf('static-assets', static fn () => app_path('Builders/PHP/Laravel/Framework/Static'));
    }

    /**
     *
     */
    public static function registerConfigBindings(): void
    {
        // TODO: Retrieve from settings...
        app()->singletonIf(
            'code-gen',
            static fn () => [
                'suppressInspections'  => true,
                'using-ea-inspections' => true,
            ]
        );
    }
}
