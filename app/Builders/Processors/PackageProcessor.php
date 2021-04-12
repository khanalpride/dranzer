<?php

namespace App\Builders\Processors;

use Closure;
use App\Builders\PHP\Laravel\Framework\PackageBuilder;

/**
 * Class PackageProcessor
 * @package App\Builders\Processors
 */
class PackageProcessor extends JSBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this
            ->processGlobalDependencies($builder)
            ->processAuthDependencies($builder)
            ->processVueDependencies($builder)
            ->processEslintDependencies($builder)
            ->processHotScript($builder);

        $next($builder);

        return true;
    }

    /**
     * @param PackageBuilder $builder
     * @return PackageProcessor
     */
    private function processAuthDependencies(PackageBuilder $builder): PackageProcessor
    {
        $authMutations = app('mutations')->for('auth');

        $authEnabled = $authMutations['config']['enabled'];

        if ($authEnabled) {
            $authModule = $authMutations['module'];

            if ($authModule === 'ui') {
                $uiConfig = $authMutations['ui'];
                $library = $uiConfig['library'] ?? 'vue';

                if ($library === 'vue') {
                    $builder->addDevDependency('bootstrap', '^4.0.0');
                    $builder->addDevDependency('jquery', '^3.2');
                    $builder->addDevDependency('popper.js', '^1.12');
                    $builder->addDevDependency('sass', '^1.20.1');
                    $builder->addDevDependency('sass-loader', '^8.0.0');
                    $builder->addDevDependency('vue', '^2.6.12');
                    $builder->addDevDependency('vue-loader', '^15.9.5');
                    $builder->addDevDependency('vue-template-compiler', '^2.6.12');
                }

                if ($library === 'react') {
                    $builder->addDevDependency('bootstrap', '^4.0.0');
                    $builder->addDevDependency('jquery', '^3.2');
                    $builder->addDevDependency('popper.js', '^1.12');
                    $builder->addDevDependency('sass', '^1.20.1');
                    $builder->addDevDependency('sass-loader', '^8.0.0');
                    $builder->addDevDependency('@babel/preset-react', '^7.0.0');
                    $builder->addDevDependency('react', '^16.2.0');
                    $builder->addDevDependency('react-dom', '^16.2.0');
                }

                if ($library === 'bootstrap') {
                    $builder->addDevDependency('bootstrap', '^4.0.0');
                    $builder->addDevDependency('jquery', '^3.2');
                    $builder->addDevDependency('popper.js', '^1.12');
                    $builder->addDevDependency('sass', '^1.20.1');
                    $builder->addDevDependency('sass-loader', '^8.0.0');
                }
            }

            if ($authModule === 'breeze') {
                $builder->addDevDependency('tailwindcss', 'npm:@tailwindcss/postcss7-compat@^2.0.1');
                $builder->addDevDependency('@tailwindcss/forms', '^0.2.1');
                $builder->addDevDependency('alpinejs', '^2.7.3');
                $builder->addDevDependency('autoprefixer', '^9.8.6');
                $builder->addDevDependency('postcss-import', '^12.0.1');
                $builder->addDevDependency('vue-loader', '^15.9.5');
                $builder->addDevDependency('vue-template-compiler', '^2.6.12');
            }
        }

        return $this;
    }

    /**
     * @param PackageBuilder $builder
     * @return PackageProcessor
     */
    private function processEslintDependencies(PackageBuilder $builder): PackageProcessor
    {
        $eslintMutations = app('mutations')->for('linters')['eslint'];

        $createEsLintConfig = $eslintMutations['create'];

        if (!$createEsLintConfig) {
            return $this;
        }

        $extends = $eslintMutations['config']['extends'];

        $installVuePlugin = $extends['vueEssential'] || $extends['vueRecommended'] || $extends['vueStronglyRecommended'];

        $builder->addDevDependency('eslint', '^7.14.0');
        $builder->addDevDependency('eslint-config-airbnb-base', '^14.2.1');
        $builder->addDevDependency('eslint-import-resolver-alias', '^1.1.2');
        $builder->addDevDependency('eslint-plugin-import', '^2.22.1');
        $builder->addDevDependency('babel-plugin-component', '^1.1.1');
        $builder->addDevDependency('babel-eslint', '^10.1.0');

        if ($installVuePlugin) {
            $builder->addDevDependency('eslint-plugin-vue', '^7.1.0');
        }

        return $this;
    }

    /**
     * @param PackageBuilder $builder
     * @return PackageProcessor
     */
    private function processGlobalDependencies(PackageBuilder $builder): PackageProcessor
    {
        $builder->addDevDependency('postcss', '^8.1');

        return $this;
    }

    /**
     * @param PackageBuilder $builder
     * @return void
     */
    private function processHotScript(PackageBuilder $builder): void
    {
        $hmrConfig = app('mutations')->for('assets')['hmr'];

        $enabled = $hmrConfig['enabled'] ?? false;

        $https = $hmrConfig['https'] ?? false;

        if ($enabled && $https) {
            $builder->useHttpsForHMR();
        }

    }

    /**
     * @param PackageBuilder $builder
     * @return PackageProcessor
     */
    private function processVueDependencies(PackageBuilder $builder): PackageProcessor
    {
        $vueSetup = app('mutations')->for('frontend')['vue'];

        $vueConfig = $vueSetup['config'] ?? [];

        $installVue = $vueConfig['install'] ?? false;

        if ($installVue) {
            $addRouter = $vueConfig['addRouter'];
            $addStore = $vueConfig['addStore'];

            $elementConfig = $vueConfig['ui']['element'];

            $installElementUI = $elementConfig['install'] ?? false;

            //babel-plugin-component
            $builder->addDevDependency('vue', '^2.6.12');
            $builder->addDevDependency('vue-loader', '^15.9.5');
            $builder->addDevDependency('vue-template-compiler', '^2.6.12');

            if ($addRouter) {
                $builder->addDevDependency('vue-router', '^3.5.1');
            }

            if ($addStore) {
                $builder->addDevDependency('vuex', '^3.6.2');
            }

            if ($installElementUI) {
                $builder->addDependency('element-ui', '^2.15.0');
            }

            $installVuetify = $vueConfig['ui']['vuetify']['install'] ?? false;

            if ($installVuetify) {
                $builder->addDependency('vuetify', '^2.4.3');

                $builder->addDevDependency('deepmerge', '^4.2.2');
                $builder->addDevDependency('sass', '^1.20.1');
                $builder->addDevDependency('sass-loader', '^8.0.0');
            }
        }

        return $this;
    }
}
