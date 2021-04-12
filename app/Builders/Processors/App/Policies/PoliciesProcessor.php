<?php

namespace App\Builders\Processors\App\Policies;

use Closure;
use App\Models\User;
use Illuminate\Support\Facades\File;
use App\Builders\Processors\PHPBuilderProcessor;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Builders\PHP\Laravel\Framework\App\Policies\PoliciesBuilder;

/**
 * Class PoliciesProcessor
 * @package App\Builders\Processors\App\Policies
 */
class PoliciesProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $projectRoot = app('project-dir');
        $policiesDirectory = $projectRoot . '/app/Policies';

        $apiConfig = app('mutations')->for('api');

        if ($apiConfig['generate']) {
            $modules = $apiConfig['modules'];

            if (count($modules)) {
                File::ensureDirectoryExists($policiesDirectory);
            }

            foreach ($modules as $module) {
                $this->buildPolicyClass($builder, $module);
            }
        }

        $next($builder);

        return true;
    }

    /**
     * @param PoliciesBuilder $builder
     * @param $module
     */
    private function buildPolicyClass(PoliciesBuilder $builder, $module): void
    {
        $blueprint = $module['blueprint'];

        $modelName = $blueprint->getName();

        if ($modelName === 'User') {
            return;
        }

        $builder
            ->setClassDefinition("{$modelName}Policy")
            ->updateClassDefinition();

        $builder->use(User::class);
        $builder->use("App\Models\\$modelName");
        $builder->use(HandlesAuthorization::class);

        $builder->setTraits(['HandlesAuthorization']);

        $viewAnyMethodBuilder = $builder->getNewMethodBuilder('viewAny');

        $viewAnyMethodBuilder
            ->addParameter($this->param('user', 'User'))
            ->addStatement($this->return($this->const(true)))
            ->setReturnType('bool')
            ->getDocBuilder()
            ->addCommentLine('Determine whether the user can view any models.')
            ->setReturnType('bool');

        $viewMethodBuilder = $builder->getNewMethodBuilder('view');

        $viewMethodBuilder
            ->addParameters([
                $this->param('user', 'User'),
                $this->param(lcfirst($modelName), $modelName),
            ])
            ->addStatement($this->return($this->const(true)))
            ->setReturnType('bool')
            ->getDocBuilder()
            ->addCommentLine('Determine whether the user can view the model.')
            ->setReturnType('bool');

        $createMethodBuilder = $builder->getNewMethodBuilder('create');

        $createMethodBuilder
            ->addParameters([
                $this->param('user', 'User'),
            ])
            ->addStatement($this->return($this->const(true)))
            ->setReturnType('bool')
            ->getDocBuilder()
            ->addCommentLine('Determine whether the user can create the model.')
            ->setReturnType('bool');

        $updateMethodBuilder = $builder->getNewMethodBuilder('update');

        $updateMethodBuilder
            ->addParameters([
                $this->param('user', 'User'),
                $this->param(lcfirst($modelName), $modelName),
            ])
            ->addStatement($this->return($this->const(true)))
            ->setReturnType('bool')
            ->getDocBuilder()
            ->addCommentLine('Determine whether the user can update the model.')
            ->setReturnType('bool');

        $deleteMethodBuilder = $builder->getNewMethodBuilder('delete');

        $deleteMethodBuilder
            ->addParameters([
                $this->param('user', 'User'),
                $this->param(lcfirst($modelName), $modelName),
            ])
            ->addStatement($this->return($this->const(true)))
            ->setReturnType('bool')
            ->getDocBuilder()
            ->addCommentLine('Determine whether the user can delete the model.')
            ->setReturnType('bool');

        $restoreMethodBuilder = $builder->getNewMethodBuilder('restore');

        $restoreMethodBuilder
            ->addParameters([
                $this->param('user', 'User'),
                $this->param(lcfirst($modelName), $modelName),
            ])
            ->addStatement($this->return($this->const(true)))
            ->setReturnType('bool')
            ->getDocBuilder()
            ->addCommentLine('Determine whether the user can restore the model.')
            ->setReturnType('bool');

        $forceDeleteMethodBuilder = $builder->getNewMethodBuilder('forceDelete');

        $forceDeleteMethodBuilder
            ->addParameters([
                $this->param('user', 'User'),
                $this->param(lcfirst($modelName), $modelName),
            ])
            ->addStatement($this->return($this->const(true)))
            ->setReturnType('bool')
            ->getDocBuilder()
            ->addCommentLine('Determine whether the user can permanently delete the model.')
            ->setReturnType('bool');

        $builder->addMethodBuilders([
            $viewAnyMethodBuilder,
            $viewMethodBuilder,
            $createMethodBuilder,
            $updateMethodBuilder,
            $deleteMethodBuilder,
            $restoreMethodBuilder,
            $forceDeleteMethodBuilder
        ]);

        $builder
            ->setFilename("{$modelName}Policy.php")
            ->toDisk();

        $builder->reset();
    }
}
