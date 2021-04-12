<?php

namespace App\Builders\Processors;

use Closure;
use App\Writers\JS\JSWriter;

/**
 * Class EslintRCProcessor
 * @package App\Builders\Processors
 */
class EslintRCProcessor extends JSBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->useDoubleQuotesForStrings();

        $eslintConfig = app('mutations')->for('linters')['eslint'];

        if ($eslintConfig['create']) {
            $config = $this->getConfig($eslintConfig['config']);
            $builder->setConfig(trim($config));
        } else {
            $builder->setCanBuild(false);
        }

        $next($builder);

        return true;
    }

    /**
     * @param $parsed
     * @return string
     */
    private function getConfig($parsed): string
    {
        $stmts = [];

        $writer = new JSWriter;

        $envObject = $this->object([
            $this->keyValueMap('browser', $this->bool($parsed['env']['browser']), true),
            $this->keyValueMap('es6', $this->bool($parsed['env']['es6']), true),
            $this->keyValueMap('node', $this->bool($parsed['env']['node'])),
        ]);

        $parserOptionsObject = $this->object([
            $this->keyValueMap('parser', 'babel-eslint'),
        ]);

        $extends = [];

        if ($parsed['extends']['airbnbBase']) {
            $extends[] = $this->string('airbnb-base');
        }

        if ($parsed['extends']['vueEssential']) {
            $extends[] = $this->string('plugin:vue/essential');
        }

        if ($parsed['extends']['vueRecommended']) {
            $extends[] = $this->string('plugin:vue/recommended');
        }

        if ($parsed['extends']['vueStronglyRecommended']) {
            $extends[] = $this->string('plugin:vue/strongly-recommended');
        }

        $extendsObject = $this->array($extends, false);

        $rulesObject = $this->object([]);

        $settingsObject = $this->object([]);

        if ($parsed['resolution']['mapJsDir']) {
            $settingsObject = $this->object([
                $this->keyValueMap('import/resolver', $this->object([
                    $this->keyValueMap('alias', $this->object([
                        $this->keyValueMap('map', $this->array([
                            $this->string('@'),
                            $this->string('./resources/js'),
                        ], false), true),
                        $this->keyValueMap('extensions', $this->array([
                            $this->string('.js'),
                            $this->string('.jsx'),
                            $this->string('.json'),
                            $this->string('.vue'),
                        ], false))
                    ]))
                ]))
            ]);
        }

        $maxLineLength = (int) $parsed['overrides']['maxLineLength'];
        $noReturnAssignment = $parsed['overrides']['noReturnAssignment'];
        $noParamReassignments = $parsed['overrides']['noParamReassignments'];

        $overrides = [];

        if ($maxLineLength !== 120 && is_numeric($maxLineLength)) {
            $overrides[] = $this->keyValueMap('max-len', $this->array([
                $this->string('error'),
                $this->object([
                    $this->keyValueMap('code', $this->number($maxLineLength))
                ])
            ], false), $noReturnAssignment);
        }

        if ($noReturnAssignment) {
            $overrides[] = $this->keyValueMap('no-return-assign', 'off', $noParamReassignments);
        }

        if ($noParamReassignments) {
            $overrides[] = $this->keyValueMap('no-param-reassign', 'off');
        }

        $overridesObject = $this->array([
            $this->object([
                $this->keyValueMap('files', $this->array([$this->string('*.*')]), true),
                $this->keyValueMap('rules', $this->object($overrides))
            ])
        ]);

        $stmts[] = $this->objectStmt($this->object([
            $this->keyValueMap('env', $envObject, true),
            $this->keyValueMap('parserOptions', $parserOptionsObject, true),
            $this->keyValueMap('extends', $extendsObject, true),
            $this->keyValueMap('rules', $rulesObject, true),
            $this->keyValueMap('settings', $settingsObject, true),
            $this->keyValueMap('overrides', $overridesObject, false),
        ]));

        $writer->setStatements($stmts);

        return $writer->toString();
    }
}
