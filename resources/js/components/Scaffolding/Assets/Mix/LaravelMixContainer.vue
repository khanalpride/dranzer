<template>
    <scaffolding-component-container :heading="heading || 'Laravel Mix Configuration'" :loading="loading || fetchingMutations">
        <row>
            <column v-if="showPaths">
                <row>
                    <column size="4">
                        <pg-labeled-input v-model="resourcePath"
                                          :tooltip="tooltips.resourcePath"
                                          :tooltip-distance="30"
                                          :tooltip-delay="250"
                                          tooltip-placement="left"
                                          label="Base Resources Path"
                                          @input="persistPaths"
                                          placeholder="e.g resources"/>
                    </column>

                    <column size="4">
                        <pg-labeled-input v-model="templatePath"
                                          :tooltip="tooltips.templatePath"
                                          tooltip-placement="top"
                                          :tooltip-distance="40"
                                          :tooltip-delay="250"
                                          label="Base Assets / Template Path"
                                          @input="persistPaths"
                                          placeholder="e.g template/, template/assets"/>
                    </column>

                    <column size="4">
                        <pg-labeled-input v-model="outputPath"
                                          :tooltip="tooltips.outputPath"
                                          :tooltip-distance="30"
                                          :tooltip-delay="250"
                                          tooltip-placement="right"
                                          label="Base Output Path"
                                          @input="persistPaths"
                                          placeholder="e.g public, dist"/>
                    </column>
                </row>
            </column>

            <column :push10="showPaths">
                <tabs-manager :path="`assets/mix/tabs/modules/${this.layoutId}`" :tabs="configModules">
                    <template :slot="module.id" v-for="module in configModules">
                        <row :key="module.id">
                            <column :size="module.layout ? module.layout.columnSize : 12" :offset="module.layout ? module.layout.offset : 0">
                                <component :key="module.name" :layout-id="layoutId" :paths="paths" :is="module.component" />
                            </column>
                        </row>
                    </template>
                </tabs-manager>
            </column>
        </row>
    </scaffolding-component-container>
</template>

<script>
import ContentCard from '@/components/Cards/ContentCard';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgInput from '@/components/Forms/PgInput';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import Separator from '@/components/Layout/Separator';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import MixConfig from '@/components/Scaffolding/Assets/Mix/Config/MixConfiguration';
import HMR from '@/components/Scaffolding/Assets/Mix/Config/HMR';
import CopyAssets from '@/components/Scaffolding/Assets/Mix/Config/CopyAssets';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import PostProcessing from '@/components/Scaffolding/Assets/Mix/Config/PostProcessing';
import TabsManager from '@/components/Tabs/TabsManager';

export default {
  name: 'LaravelMixContainer',
  mixins: [asyncImports, mutations],
  props: {
    heading: String,
    showPaths: Boolean,
    requiredModules: Array,
    layoutId: [String, Number],
  },
  components: {
    TabsManager,
    ScaffoldingComponentContainer,
    HMR,
    MixConfig,
    IndeterminateProgressBar,
    Separator,
    PgLabeledInput,
    PgInput,
    Column,
    Row,
    ContentCard,
  },
  data() {
    return {
      loading: false,

      activeTab: 'js-modules',

      resourcePath: 'resources',
      templatePath: 'template',
      outputPath: 'public',

      tooltips: {
        resourcePath: 'The base path of all compilable assets.'
            + ' The default for a Laravel project is <span class="text-complete">resources</span>'
            + ' and unless you have a reason not to, you should place your js and vendor assets inside this directory.',
        templatePath: 'This path will be used to fetch vendor stylesheets, libraries, fonts and so on.',
        outputPath: 'This path is where all the compiled assets are placed.',
      },

      modules: [
        {
          id: 'asset-post-processing',
          label: 'Asset Post-Processing',
          name: 'asset-post-processing',
          component: PostProcessing,
        },
        {
          id: 'copy-assets',
          label: 'Copy Assets',
          name: 'copy-assets',
          component: CopyAssets,
          layout: {
            columnSize: 8,
            offset: 2,
          },
        },
        {
          id: 'hmr',
          label: 'HMR',
          name: 'hmr',
          component: HMR,
        },
        {
          id: 'misc',
          label: 'Misc',
          name: 'misc',
          component: MixConfig,
        },
      ],

      configModules: [],
    };
  },
  computed: {
    paths() {
      const resourcePath = this.resourcePath.trim() === '' ? 'resources' : this.resourcePath.trim();
      const templatePath = this.templatePath.trim();
      const outputPath = this.outputPath.trim() === '' ? 'public' : this.outputPath.trim();

      return {
        resourcePath,
        templatePath,
        outputPath,
      };
    },
  },
  async created() {
    if (this.requiredModules && this.requiredModules.length) {
      this.configModules = this.modules.filter((mod) => this.requiredModules.includes(mod.name));
    } else {
      this.configModules = this.modules.filter((mod) => ['hmr', 'misc'].includes(mod.name));
    }

    this.loading = true;
    await this.syncPaths();
    this.loading = false;
  },
  methods: {
    async syncPaths() {
      const { data } = await this.mutation({ path: `assets/mix/paths/${this.layoutId}` });
      const paths = data.value || {};

      this.resourcePath = paths.resourcePath || this.resourcePath;
      this.templatePath = paths.templatePath || this.templatePath;
      this.outputPath = paths.outputPath || this.outputPath;
    },

    persistPaths() {
      const payload = {
        name: 'Mix Template Paths',
        path: `assets/mix/paths/${this.layoutId}`,
        value: this.paths,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
