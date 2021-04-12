<template>
    <content-card heading="Concatenation and Minification">
        <div v-if="fetchingMutations || loading">
            <row>
                <column size="4" offset="4">
                    <p class="text-center">Restoring Configuration...</p>
                    <indeterminate-progress-bar />
                </column>
            </row>
        </div>

        <div v-else>
            <row>
                <column size="8" offset="2">
                    <row>
                        <column>
                            <tabs-manager :path="`assets/mix/config/pp/tabs/active/${layoutId}`" :tabs="tabs">
                                <template :slot="tab.id" v-for="tab in tabs">
                                    <post-processor-asset-category :category="tab.category"
                                                                   :category-heading="tab.categoryHeading"
                                                                   :mix-paths="paths"
                                                                   :persisted-assets="tab.assets"
                                                                   :output-extension="tab.ext"
                                                                   :groups="tab.groups"
                                                                   :key="tab.id"
                                                                   @grouping-changed="handleAssetGroups($event, tab)" />
                                </template>
                            </tabs-manager>
                        </column>
                    </row>
                </column>
            </row>
        </div>
    </content-card>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import ContentCard from '@/components/Cards/ContentCard';
import Column from '@/components/Layout/Grid/Column';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import PostProcessorAssetCategory from '@/components/Scaffolding/Assets/Mix/Config/Components/PostProcessing/PostProcessorAssetCategory';
import TabsManager from '@/components/Tabs/TabsManager';

export default {
  name: 'PostProcessing',
  props: {
    layoutId: [String, Number],
    paths: {},
  },
  mixins: [asyncImports, mutations],
  components: {
    TabsManager,
    PostProcessorAssetCategory,
    IndeterminateProgressBar,
    Column,
    ContentCard,
    Row,
  },
  data() {
    return {
      loading: false,

      scriptGroups: [],
      stylesheetGroups: [],

      scripts: [],

      stylesheets: [],

      mixPaths: [],

      checkedScriptsTargetSplit: null,
    };
  },
  computed: {
    tabs() {
      return [
        {
          id: 'scripts',
          label: 'Scripts',
          category: 'script',
          categoryHeading: 'Scripts',
          assets: this.scripts,
          groups: this.scriptGroups,
          ext: 'js',
        },
        {
          id: 'stylesheets',
          label: 'Stylesheets',
          category: 'stylesheet',
          categoryHeading: 'Stylesheet',
          assets: this.stylesheets,
          groups: this.stylesheetGroups,
          ext: 'css',
        },
      ];
    },
  },
  async created() {
    this.loading = true;

    await this.syncScripts();
    await this.syncStylesheets();
    await this.syncGroups();

    this.loading = false;
  },
  methods: {
    async syncGroups() {
      const { data } = await this.mutation({ path: `assets/mix/config/pp/groups/scripts/${this.layoutId}` });
      this.scriptGroups = data.value || [];

      {
        // eslint-disable-next-line no-shadow
        const { data } = await this.mutation({ path: `assets/mix/config/pp/groups/stylesheets/${this.layoutId}` });
        this.stylesheetGroups = data.value || [];
      }
    },

    async syncScripts() {
      const { data } = await this.mutation({ path: `assets/template/scripts/${this.layoutId}` });
      this.scripts = (data.value || []).filter((s) => s.enabled);
    },

    async syncStylesheets() {
      const { data } = await this.mutation({ path: `assets/template/stylesheets/${this.layoutId}` });
      this.stylesheets = (data.value || []).filter((s) => s.enabled);
    },

    contains(substr, string) {
      return string.indexOf(substr) > -1;
    },

    handleAssetGroups(groups, category) {
      const payload = {
        name: 'Post Processing Asset Group',
        path: `assets/mix/config/pp/groups/${category.id}/${this.layoutId}`,
        value: groups,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
