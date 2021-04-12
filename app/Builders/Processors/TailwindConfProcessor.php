<?php

namespace App\Builders\Processors;

use Closure;
use App\Writers\JS\JSWriter;

/**
 * Class TailwindConfProcessor
 * @package App\Builders\Processors
 */
class TailwindConfProcessor extends JSBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $tailwindConfig = app('mutations')->for('frontend')['tailwind'];

        $authMutations = app('mutations')->for('auth');

        $usingBreezeAuthModule = $authMutations['install'] && $authMutations['module'] === 'breeze';

        if (!$usingBreezeAuthModule && !($tailwindConfig['install'] ?? false)) {
            $builder->setCanBuild(false);
        } else {
            $builder->setConfig($this->getConfig($tailwindConfig, $usingBreezeAuthModule));
        }

        $next($builder);

        return true;
    }

    /**
     * @param $tailwindConfig
     * @param $usingBreezeAuthModule
     * @return string
     */
    private function getConfig($tailwindConfig, $usingBreezeAuthModule): string
    {
        // TODO: Refactor (extract breeze processing).

        $imports = [];

        if ($usingBreezeAuthModule) {
            $imports[] = $this->require('tailwindcss/defaultTheme', 'defaultTheme');
        }

        $screen = $tailwindConfig['screen'];
        $defaultColors = $tailwindConfig['colors']['default'];
        $customColors = $tailwindConfig['colors']['custom'];
        $defaultSpacing = $tailwindConfig['spacing']['default'];
        $customSpacing = $tailwindConfig['spacing']['custom'];

        $colorsConfig = $tailwindConfig['colorsConfig'];

        $screenStmts = [];

        foreach ($screen as $screenConf) {
            $alias = $screenConf['alias'] ?? null;

            if (!$alias) {
                continue;
            }

            $var = preg_match('/^\d+/', $alias) ? $this->string($alias) : $this->var($alias);

            $breakpointStmts = [];

            $breakpoints = collect($screenConf['breakpoints'] ?? [])
                ->filter(fn ($b) => (($b['min'] ?? false) || ($b['max'] ?? false)) && (($b['minBreakpoint'] ?? null) || ($b['maxBreakpoint'] ?? null)))
                ->toArray();


            foreach ($breakpoints as $breakpoint) {
                $min = (($breakpoint['min'] ?? false) && ($breakpoint['minBreakpoint'] ?? false)) ? $breakpoint['minBreakpoint'] : null;
                $max = (($breakpoint['max'] ?? false) && ($breakpoint['maxBreakpoint'] ?? false)) ? $breakpoint['maxBreakpoint'] : null;

                if ($min && $max) {
                    if (is_numeric($min)) {
                        $min .= 'px';
                    }
                    if (is_numeric($max)) {
                        $max .= 'px';
                    }

                    $breakpointStmts[] = $this->object([
                        $this->keyValueMap($this->string('min'), $this->string($min), true),
                        $this->keyValueMap($this->string('max'), $this->string($max)),
                    ]);
                    continue;
                }

                $breakpointStmts[] = count($breakpoints) > 1 ? $this->object([
                    $this->keyValueMap(
                        $this->var($min ? 'min' : 'max'),
                        $this->string($min ?? $max),
                        true,
                    )
                ]) : $this->string($min ?? $max);
            }

            if (count($breakpointStmts)) {
                $screenStmts[] = count($breakpointStmts) > 1
                    ? $this->keyValueMap($var, $this->array($breakpointStmts)) : $this->keyValueMap($var, $breakpointStmts[0], true);
            }
        }

        $colorStmts = [];

        if ($colorsConfig['useDefaultColors']) {
            $imports[] = $this->require('tailwindcss/colors', 'colors');
        }

        if ($colorsConfig['transparent']) {
            $colorStmts[] = $this->keyValueMap($this->var('transparent'), $this->string('transparent'), true);
        }

        if ($colorsConfig['current']) {
            $colorStmts[] = $this->keyValueMap($this->var('current'), $this->string('currentColor'), true);
        }

        $addedDefaultColorNames = [];

        if ($colorsConfig['useDefaultColors']) {
            foreach ($defaultColors as $color) {
                $name = $color['name'] ?? '';

                if ($name === '') {
                    continue;
                }

                if (!($color['enabled'] ?? true)) {
                    continue;
                }

                $name = trim(implode('-', array_map(static fn ($word) => strtolower($word), explode(' ', $name))));

                $color = $color['id'] ?? null;

                if (!$name || $name === '' || !$color) {
                    continue;
                }

                $colorStmts[] = $this->keyValueMap($this->string($name), $this->var($color), true);

                $addedDefaultColorNames[] = $name;
            }
        }

        foreach ($customColors as $color) {
            $name = $color['name'] ?? '';

            if ($name === '') {
                continue;
            }

            $name = trim(implode('-', array_map(static fn ($word) => strtolower($word), explode(' ', $name))));

            $colorValue = $color['color'] ?? null;

            if (!$name || $name === '' || !$colorValue || in_array($name, $addedDefaultColorNames, true)) {
                continue;
            }

            $shades = $color['shades'] ?? [];

            $shadeStmts = [];

            foreach ($shades as $shade) {
                $shadeName = $shade['name'] ?? '';
                $shadeColor = $shade['color'] ?? null;

                if (!$shadeName || $shadeName === '' || !$shadeColor) {
                    continue;
                }

                $shadeStmts[] = $this->keyValueMap($this->string($shadeName), $this->string($shadeColor), true);
            }

            if (count($shadeStmts)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $shadeStmts = array_merge([
                    $this->keyValueMap($this->string('DEFAULT'), $this->string($colorValue), true),
                ], $shadeStmts);
            }

            $colorStmts[] = !count($shadeStmts)
                ? $this->keyValueMap($this->string($name), $this->string($colorValue), true)
                : $this->keyValueMap($this->string($name), $this->object($shadeStmts), true);
        }

        $spacingStmts = [];

        $hasAnOverride = collect($defaultSpacing)->first(fn ($s) => collect($customSpacing)->first(fn ($cs) => $cs['name'] === $s['name']));

        if ($hasAnOverride || count($defaultSpacing) !== 35) {
            foreach ($defaultSpacing as $space) {
                $customSpace = collect($customSpacing)->first(fn ($s) => $s['name'] === $space['name']);

                $name = $space['name'];
                $value = $customSpace ? $customSpace['value'] : $space['value'];

                $spacingStmts[] = $this->keyValueMap($this->string($name), $this->string($value ?? ''), true);
            }
        }

        $extendedSpacingStmts = [];
        foreach ($customSpacing as $space) {
            $defaultSpace = collect($defaultSpacing)->first(fn ($s) => $s['name'] === $space['name']);

            if ($defaultSpace && (count($defaultSpacing) !== 35 || $hasAnOverride)) {
                continue;
            }

            $name = $space['name'];
            $value = $space['value'];

            $extendedSpacingStmts[] = $this->keyValueMap($this->string($name), $this->string($value ?? ''), true);
        }

        $stmts = $imports;

        if (count($stmts)) {
            $stmts[] = $this->nopStmt();
        }

        $purgeable = [];

        $extendKeys = [];

        if (count($extendedSpacingStmts)) {
            $extendKeys[] = $this->keyValueMap(
                $this->var('spacing'),
                $this->object($extendedSpacingStmts),
                true,
            );
        }

        if ($usingBreezeAuthModule) {
            $extendKeys[] = $this->keyValueMap(
                $this->var('fontFamily'),
                $this->object([
                    $this->keyValueMap(
                        $this->var('sans'),
                        $this->array([
                            $this->string('Nunito'),
                            $this->raw('...defaultTheme.fontFamily.sans'),
                        ]),
                        true,
                    )
                ]),
                true,
            );
        }

        if ($usingBreezeAuthModule) {
            $purgeable = [
                $this->string('./storage/framework/views/*.php'),
                $this->string('./resources/views/**/*.blade.php'),
            ];
        }

        $extendedVariants = [];

        if ($usingBreezeAuthModule) {
            $extendedVariants[] = $this->keyValueMap(
                $this->var('opacity'),
                $this->array([$this->string('disabled')], true),
                true,
            );
        }

        $plugins = [];

        if ($usingBreezeAuthModule) {
            $plugins[] = $this->funcCall('require', [$this->string('@tailwindcss/forms')]);
        }

        $themeKeys = [];

        if (count($screenStmts)) {
            $themeKeys[] = $this->keyValueMap(
                $this->var('screens'),
                $this->object($screenStmts),
                true,
            );
        }

        if (count($spacingStmts)) {
            $themeKeys[] = $this->keyValueMap(
                $this->var('spacing'),
                $this->object($spacingStmts),
                true,
            );
        }

        if (count($colorStmts)) {
            $themeKeys[] = $this->keyValueMap(
                $this->var('colors'),
                $this->object($colorStmts),
                true,
            );
        }

        if (count($extendKeys)) {
            $themeKeys[] = $this->keyValueMap(
                $this->var('extend'),
                $this->object($extendKeys),
                true,
            );
        }

        $stmts[] = $this->modExports(
            $this->object([
                $this->keyValueMap(
                    $this->var('purge'),
                    $this->array($purgeable),
                    true,
                ),
                $this->keyValueMap(
                    $this->var('theme'),
                    $this->object($themeKeys),
                    true,
                ),
                $this->keyValueMap(
                    $this->var('variants'),
                    $this->object([
                        $this->keyValueMap(
                            $this->var('extend'),
                            $this->object($extendedVariants),
                            true
                        )
                    ]),
                    true,
                ),
                $this->keyValueMap(
                    $this->var('plugins'),
                    $this->array($plugins),
                    true
                )
            ])
        );

        return (new JSWriter($stmts))->toString();
    }
}
