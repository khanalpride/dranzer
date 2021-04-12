<?php

namespace App\Builders\Processors\Modules;

use Closure;
use Illuminate\Support\Str;
use App\Writers\HTMLWriter;
use App\Writers\JS\JSWriter;
use Illuminate\Support\Facades\File;
use App\Builders\Processors\JSBuilderProcessor;

/**
 * Class VueModuleProcessor
 * @package App\Builders\Processors\Modules
 */
class VueModuleProcessor extends JSBuilderProcessor
{
    /**
     * @var array
     */
    private $imports = [];
    /**
     * @var array
     */
    private $uses = [];
    /**
     * @var array
     */
    private $customUses = [];
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

        // Frontend
        $vueSetup = app('mutations')->for('frontend')['vue'];

        $config = $vueSetup['config'];

        $installVue = $config['install'];

        if ($installVue) {
            $stmts = [];

            $writer = new JSWriter;

            $createMain = $config['createMain'] ?? true;
            $addRouter = $config['addRouter'];
            $addStore = $config['addStore'];

            $components = $vueSetup['components'] ?? [];

            $renderComponent = collect($components)->first(fn ($c) => $c['render']);

            $renderComponentProp = '';

            $renderComponentCode = null;
            $renderComponentData = null;

            $this->imports[] = $this->import('vue', 'Vue');

            if ($addStore) {
                $this->imports[] = $this->import('../store', 'store');
                $this->createStore();
            }

            if ($addRouter) {
                $this->imports[] = $this->import('../router', 'router');
                $this->createRouter();
            }

            $elementConfig = $config['ui']['element'];

            $installElementUI = $elementConfig['install'] ?? false;
            $elementImportType = $elementConfig['importType'] ?? 'full';
            $onDemandComponents = $elementConfig['onDemandComponents'] ?? [
                    'Button',
                    'Select'
                ];

            if ($installElementUI) {
                $this->processElementUI($elementImportType, $onDemandComponents);

                if ($elementImportType === 'full' || ($elementImportType === 'onDemand' && in_array('Card', $onDemandComponents, true))) {
                    $htmlWriter = new HTMLWriter;

                    $htmlWriter->startTag('div', ['style' => 'padding: 40px 80px 80px 40px'])
                        ->startTag('el-card', [])
                        ->startTag('p', [], 'Element UI up and running!')
                        ->closeTag()
                        ->closeTag()
                        ->closeTag();

                    $renderComponentCode = $htmlWriter->flush();
                }
            }

            $installVuetify = $config['ui']['vuetify']['install'] ?? false;

            if ($installVuetify) {
                $webpackConfig = $this->getWebpackConfigForVuetify();
                File::put($this->projectRoot . '/webpack.config.js', $webpackConfig);

                File::ensureDirectoryExists($this->projectRoot . '/resources/js/plugins');
                $vuetifyPluginCode = $this->getVuetifyPluginCode();
                File::put($this->projectRoot . '/resources/js/plugins/vuetify.js', $vuetifyPluginCode);

                $this->imports[] = $this->nopStmt();
                $this->imports[] = $this->import('../plugins/vuetify.js', 'vuetify');
                $this->imports[] = $this->nopStmt();

                $renderComponentCode = $this->getVuetifyRenderComponentCode();
                $renderComponentData = "drawer: false,";
            }

            if ($renderComponent) {
                $componentName = $renderComponent['name'];

                $filename = $this->getComponentFilename($componentName);

                $dir = $this->getComponentDir($componentName);

                $this->imports[] = $this->import("../components$dir$filename", $filename);

                $renderComponentProp = "render: h => h($filename),";
            }

            if ($createMain) {
                $vueInstanceCode = $this->getVueInstanceCode($addRouter, $addStore, $installVuetify, $renderComponentProp);

                foreach ($this->imports as $import) {
                    $stmts[] = $import;
                }

                if (count($this->imports)) {
                    $stmts[] = $this->nopStmt();
                }

                foreach ($this->uses as $use) {
                    $options = $this->raw('');

                    if (count($use['options'] ?? [])) {
                        $options = $this->raw('{ ' . collect($use['options'])->join(', ') . ' }');
                    }

                    $stmts[] = $this->funcCallStmt(
                        $this->funcCall(
                            'use',
                            [
                                $this->raw($use['lib']),
                                $options
                            ],
                            $this->var('Vue'),
                        )
                    );
                }

                if (count($this->uses)) {
                    $stmts[] = $this->nopStmt();
                }

                foreach ($this->customUses as $use) {
                    $stmts[] = $this->funcCallStmt(
                        $this->funcCall(
                            $use['call'],
                            collect($use['params'])->map(fn ($p) => $this->raw($p))->toArray(),
                            $this->var($use['var'])
                        )
                    );
                }

                if (count($this->uses)) {
                    $stmts[] = $this->nopStmt();
                }

                $stmts[] = $this->rawStmt($this->raw($vueInstanceCode));

                $mainCode = $writer->setStatements($stmts)
                    ->toString();

                File::ensureDirectoryExists($this->projectRoot . '/resources/js/modules');
                File::put($this->projectRoot . '/resources/js/modules/main.js', $mainCode);
            }

            if (count($components)) {
                $componentsDir = $this->projectRoot . '/resources/js/components';
                File::ensureDirectoryExists($componentsDir);

                foreach ($components as $component) {
                    $name = $component['name'] ?? null;

                    if (!$name) {
                        continue;
                    }

                    $template = '';

                    if ($name === ($renderComponent['name'] ?? null) && $renderComponentCode) {
                        $template = $renderComponentCode;
                    }

                    $dir = $this->getComponentDir($name);

                    if (!empty($dir)) {
                        File::ensureDirectoryExists("$componentsDir/$dir");
                    }

                    $filename = pathinfo($name, PATHINFO_FILENAME);
                    $filename = str_replace('.vue', '', $filename);

                    if ($dir) {
                        $componentsDir .= '/';
                    }

                    $path = "$componentsDir$dir/$filename.vue";

                    File::put($path, $this->getComponentCode($filename, $template, $renderComponentData));
                }
            }

            $authMutations = app('mutations')->for('auth');

            $authEnabled = $authMutations['config']['enabled'];

            if (!$authEnabled) {
                $staticAssetsDir = app('static-assets');

                File::ensureDirectoryExists($this->projectRoot . '/resources/js');

                File::copy($staticAssetsDir . '/resources/js/vue/app.js', $this->projectRoot . '/resources/js/app.js');
                File::copy($staticAssetsDir . '/resources/js/vue/bootstrap.js', $this->projectRoot . '/resources/js/bootstrap.js');
            }
        }

        $next($builder);

        return true;
    }

    /**
     * @param $importType
     * @param $onDemandComponents
     * @return void
     */
    private function processElementUI($importType, $onDemandComponents): void
    {
        $this->imports[] = $this->nopStmt();

        if ($importType === 'full') {
            $this->imports[] = $this->import('element-ui', 'ElementUI');
            $this->imports[] = $this->import('element-ui/lib/locale/lang/en', 'locale');
            $this->uses[] = [
                'lib'     => 'ElementUI',
                'options' => ['locale']
            ];
        } else {
            $this->imports[] = $this->import('element-ui', $onDemandComponents, true);
            collect($onDemandComponents)->each(fn ($c) => $this->uses[] = [
                'lib'     => $c,
                'options' => []
            ]);
        }

        $this->imports[] = $this->nopStmt();
        $this->imports[] = $this->import('element-ui/lib/theme-chalk/index.css');
        $this->imports[] = $this->nopStmt();

        if ($importType !== 'full') {
            $this->imports[] = $this->import('element-ui/lib/locale/lang/en', 'lang');
            $this->imports[] = $this->import('element-ui/lib/locale', 'locale');
            $this->imports[] = $this->nopStmt();
            $this->customUses[] = [
                'var'    => 'locale',
                'call'   => 'use',
                'params' => ['lang']
            ];
        }

    }

    /**
     * @return void
     */
    private function createRouter(): void
    {
        File::ensureDirectoryExists($this->projectRoot . '/resources/js/router');

        $writer = new JSWriter;

        $routerCode = $writer->setStatements([
            $this->import('vue', 'Vue'),
            $this->import('vue-router', 'VueRouter'),
            $this->nopStmt(),
            $this->rawStmt(
                $this->raw('Vue.use(VueRouter);')
            ),
            $this->nopStmt(),
            $this->rawStmt(
                $this->raw(
                    "export default new VueRouter({
  routes: [
    {
        path: '/',
     // component: ...
    }
],
});"
                )
            )
        ])->toString();

        File::put($this->projectRoot . '/resources/js/router/index.js', $routerCode);

    }

    /**
     * @return void
     */
    private function createStore(): void
    {
        File::ensureDirectoryExists($this->projectRoot . '/resources/js/store');

        $writer = new JSWriter;

        $storeCode = $writer->setStatements([
            $this->import('vue', 'Vue'),
            $this->import('vuex', 'Vuex'),
            $this->nopStmt(),
            $this->rawStmt(
                $this->raw('Vue.use(Vuex);')
            ),
            $this->nopStmt(),
            $this->rawStmt(
                $this->raw(
                    "export default new Vuex.Store({
  modules: {},
});"
                )
            )
        ])->toString();

        File::put($this->projectRoot . '/resources/js/store/index.js', $storeCode);

    }

    /**
     * @param $addRouter
     * @param $addStore
     * @param $installVuetify
     * @param $renderComponentProp
     * @return string|string[]
     */
    private function getVueInstanceCode($addRouter, $addStore, $installVuetify, $renderComponentProp)
    {
        $code = '';

        if ($addStore && $addRouter) {
            $code = "
new Vue({
  store,
  router,
  $renderComponentProp
}).\$mount('#app');";
        }

        if ($addStore && !$addRouter) {
            $code = "
new Vue({
  store,
  $renderComponentProp
}).\$mount('#app');";
        }

        if (!$addStore && $addRouter) {
            $code = "
new Vue({
  router,
  $renderComponentProp,
}).\$mount('#app');";
        }

        if (!$addStore && !$addRouter) {
            $code = "
new Vue({
  $renderComponentProp,
}).\$mount('#app');";
        }

        if ($installVuetify) {
            $code = str_replace('new Vue({', 'new Vue({' . PHP_EOL . "\t" . 'vuetify,', $code);
        }

        return $code;
    }

    /**
     * @param $componentName
     * @return string|string[]
     */
    private function getComponentFilename($componentName)
    {
        $filename = pathinfo($componentName ?? '', PATHINFO_FILENAME);

        return str_ireplace('.vue', '', $filename);
    }

    /**
     * @param $componentName
     * @return array|string|string[]
     */
    private function getComponentDir($componentName)
    {
        $dir = pathinfo($componentName, PATHINFO_DIRNAME);

        if ($dir === '.') {
            $dir = '';
        }

        if (!Str::startsWith($dir, '/')) {
            $dir = "/$dir";
        }

        if (!Str::endsWith($dir, '/')) {
            $dir = "$dir/";
        }

        if (!$dir || trim($dir) === '') {
            $dir = './';
        }

        return $dir;
    }

    /**
     * @return string
     */
    private function getVuetifyPluginCode(): string
    {
        return <<<PLUGIN
import Vue from 'vue'
import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'

Vue.use(Vuetify)

const opts = {}

export default new Vuetify(opts)
PLUGIN;

    }

    /**
     * @return string
     */
    private function getWebpackConfigForVuetify(): string
    {
        return <<<CONFIG
module.exports = {
  module: {
    rules: [
      {
        test: /\.s[ca]ss$/,
        use: [
          'vue-style-loader',
          'css-loader',
          {
            loader: 'sass-loader',
            options: {
              implementation: require('sass'),
              sassOptions: {
                indentedSyntax: true
              },
            },
          },
        ],
      },
    ],
  }
}
CONFIG;

    }

    /**
     * @return string
     */
    private function getVuetifyRenderComponentCode(): string
    {
        return <<<CODE
<v-app>
    <v-navigation-drawer
      color="grey"
      v-model="drawer"
      app
    >
      <v-list>
        <v-list-item link>
          <v-list-item-content>
            <v-list-item-title class="title">
              App
            </v-list-item-title>
            <v-list-item-subtitle>Sub-Heading</v-list-item-subtitle>
          </v-list-item-content>
        </v-list-item>
      </v-list>

      <v-divider></v-divider>

      <v-list
        nav
        dense
      >
        <v-list-item link>
          <v-list-item-icon>
            <v-icon>mdi-folder</v-icon>
          </v-list-item-icon>
          <v-list-item-title>My Files</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar
      color="black"
      dense
      dark
      app
    >
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>

      <v-toolbar-title>Dranzer</v-toolbar-title>

      <v-spacer></v-spacer>

      <v-btn icon>
        <v-icon>mdi-magnify</v-icon>
      </v-btn>

      <v-menu
        left
        bottom
      >
        <template v-slot:activator="{ on, attrs }">
          <v-btn
            icon
            v-bind="attrs"
            v-on="on"
          >
            <v-icon>mdi-dots-vertical</v-icon>
          </v-btn>
        </template>

        <v-list>
          <v-list-item>
            <v-list-item-title>Settings</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Sizes your content based upon application components -->
    <v-main>

      <!-- Provides the application the proper gutter -->
      <v-container fluid>

        <!-- If using vue-router -->
        <router-view/>

      </v-container>

    </v-main>

    <v-footer app>
      <!-- -->
    </v-footer>
  </v-app>
CODE;

    }

    /**
     * @param $componentName
     * @param string $template
     * @param string $data
     * @return string
     */
    private function getComponentCode($componentName, $template = '', $data = ''): string
    {
        return "
<template>
    $template
</template>

<script>
export default {
  name: '$componentName',
  props: {

  },
  components: {

  },
  data() {
    return {
        $data
    }
  },
  computed: {

  },
  watch: {

  },
  methods: {

  }
};
</script>

<style scoped>

</style>
";
    }
}
