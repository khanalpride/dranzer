<?php

namespace App\Builders\Processors\App\Http\Requests;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Http\FormRequest;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\App\Http\Requests\FormRequestsBuilder;

/**
 * Class FormRequestsProcessor
 * @package App\Builders\Processors\App\Http\Requests
 */
class FormRequestsProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $projectRoot = app('project-dir');

        $mappedRules = app('mutations')->for('validation')['rules'];

        if (count($mappedRules)) {
            File::ensureDirectoryExists($projectRoot . '/app/Http/Requests');
        }

        foreach ($mappedRules as $mappedRule) {
            $this->buildRequest($builder, $mappedRule);
        }

        $next($builder);

        return true;
    }

    /**
     * @param FormRequestsBuilder $builder
     * @param $mappedRule
     */
    private function buildRequest(FormRequestsBuilder $builder, $mappedRule): void
    {
        foreach ([
                     'Create',
                     'Update'
                 ] as $type) {
            $model = $mappedRule['model'];
            $rules = $mappedRule['rules'];
            $auth = $mappedRule['auth'] ?? 'user';

            if (!count($rules)) {
                return;
            }

            $builder->use(FormRequest::class);

            $className = "${type}${model}Request";

            $builder
                ->setClassDefinition($className, 'App\Http\Requests', 'FormRequest')
                ->updateClassDefinition();

            $authorizeMethodBuilder = $builder->getNewMethodBuilder('authorize');
            $authorizeMethodBuilder->setReturnType('bool');

            if ($model === 'User') {
                $authorizeMethodBuilder->addStatement(
                    $this->return($this->const(true))
                );
            } else {
                if ($auth === 'user') {
                    $authStmt = $this->chainedFuncCalls([
                        $this->funcCall('auth'),
                        $this->funcCall('check')
                    ]);
                } else {
                    $authStmt = $this->const((bool) $auth);
                }

                $authorizeMethodBuilder->addStatement(
                    $this->return($authStmt)
                );
            }

            $authorizeMethodBuilder
                ->getDocBuilder()
                ->addCommentLine('Determine if the user is authorized to make this request.')
                ->setReturnType('bool');

            $builder->addMethodBuilder($authorizeMethodBuilder);

            $ruleStmts = [];

            foreach ($rules as $rule) {
                if (!($rule['name'] ?? null) || !($rule['compiled'] ?? null)) {
                    continue;
                }

                $ruleStmts[] = $this->assoc(
                    $this->string($rule['name']),
                    $this->string($rule['compiled']),
                );
            }

            $rulesMethodBuilder = $builder->getNewMethodBuilder('rules');
            $rulesMethodBuilder
                ->setReturnType('array')
                ->addStatement(
                    $this->return($this->arr($ruleStmts))
                )
                ->getDocBuilder()
                ->addCommentLine('Get the validation rules that apply to the request.')
                ->setReturnType('string[]');

            $builder->addMethodBuilder($rulesMethodBuilder);

            $builder
                ->setFilename("$className.php")
                ->toDisk();

            $builder->reset();
        }
    }
}
