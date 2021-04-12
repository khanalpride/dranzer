<template>
    <scaffolding-component-container :heading="`${heading || 'Configure Template Assets Definition'}`" :loading="loading || fetchingMutations">
        <row>
            <column>
                <usage-instructions modal-heading="Assets Definition">
                    <p>This section allows you to define the static assets for your template / base layout.</p>
                </usage-instructions>
            </column>
            <column>
                <row>
                    <column size="8" offset="2">
                        <el-tabs value="stylesheets">
                            <el-tab-pane label="Stylesheets" name="stylesheets" v-if="options.stylesheets">
                              <asset-category filter-category="Stylesheet" :filters="filters.stylesheets"
                                              @add="addFilter('stylesheets')"
                                              @delete="deleteFilter($event, 'stylesheets')"
                                              @parsed="linksParsed('stylesheets', $event)"
                                              @updated="handleAssetUpdated"/>
                            </el-tab-pane>
                          <el-tab-pane label="Scripts" name="scripts" v-if="options.scripts">
                            <asset-category filter-category="Script" :filters="filters.scripts"
                                            @add="addFilter('scripts')" @delete="deleteFilter($event, 'scripts')"
                                            @parsed="linksParsed('scripts', $event)" link-extraction-attribute="src"/>
                          </el-tab-pane>
                          <el-tab-pane label="Images" name="images" v-if="options.images">
                            <asset-category filter-category="Image" :filters="filters.images" @add="addFilter('images')"
                                            @delete="deleteFilter($event, 'images')" link-extraction-attribute="src"/>
                          </el-tab-pane>
                        </el-tabs>
                    </column>
                </row>
            </column>
        </row>
    </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import UsageInstructions from '@/components/Help/UsageInstructions';
import AssetCategory from '@/components/Scaffolding/Assets/Template/AssetCategory';

export default {
  name: 'TemplateDefinitionContainer',
  props: {
    heading: String,
    layoutId: [String, Number],
  },
  mixins: [asyncImports, mutations],
  components: {
    AssetCategory,
    UsageInstructions,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  data() {
    return {
      loading: false,

      definitionTab: 'scripts',

      options: {
        scripts: true,
        stylesheets: true,
        images: true,
      },

      definition: {
        scripts: [],
        stylesheets: [],
        images: [],
      },
    };
  },
  computed: {
    isEmptyDefinition() {
      return !this.definition.scripts.length && !this.definition.stylesheets.length && !this.definition.images.length;
    },
  },
  async created() {
    this.$emit('init-start');
    this.loading = true;
    await this.syncDefinitionTab();
    await this.syncScripts();
    await this.syncStylesheets();
    await this.syncImages();
    this.loading = false;
    this.$emit('init-end');
  },
  methods: {
    async syncDefinitionTab() {
      const { data } = await this.mutation({ path: `assets/template/tabs/definition/${this.layoutId}` });
      this.definitionTab = data.value || this.definitionTab;
    },

    async syncScripts() {
      const { data } = await this.mutation({ path: `assets/template/scripts/${this.layoutId}` });
      this.definition.scripts = data.value || this.definition.scripts;
    },

    async syncStylesheets() {
      const { data } = await this.mutation({ path: `assets/template/stylesheets/${this.layoutId}` });
      this.definition.stylesheets = data.value || this.definition.stylesheets;
    },

    async syncImages() {
      const { data } = await this.mutation({ path: 'assets/template/images' });
      this.definition.images = data.value || this.definition.images;
    },

    addFilter(type, filter = '') {
      this.filters[type].push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        enabled: true,
        filter,
      });
    },

    deleteFilter(filter, type) {
      const fIndex = this.filters[type].findIndex((f) => f.id === filter.id);
      if (fIndex > -1) {
        this.filters[type].splice(fIndex, 1);
      }
    },

    beforeUpload() {
      return false;
    },

    persistScripts() {
      this.$nextTick(() => {
        const name = 'Template Scripts';
        const path = `assets/template/scripts/${this.layoutId}`;
        const value = this.definition.scripts.filter((s) => s.name.trim() !== '');

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    persistStylesheets() {
      this.$nextTick(() => {
        const name = 'Template Stylesheets';
        const path = `assets/template/stylesheets/${this.layoutId}`;
        const value = this.definition.stylesheets.filter((s) => s.name.trim() !== '');

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    persistImages() {
      this.$nextTick(() => {
        const name = 'Template Images';
        const path = 'assets/template/images';
        const value = this.definition.images.filter((s) => s.name.trim() !== '');

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    persistDefinitionTab(tab) {
      this.$nextTick(() => {
        const name = 'Active Definition Tab';
        const path = `assets/template/tabs/definition/${this.layoutId}`;
        const value = tab.name;

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    hasFilter(type, filter) {
      return this.filters[type].find((f) => f.filter === filter) !== undefined;
    },

    linksParsed(type, parsed) {
      parsed.forEach((p) => {
        if (!this.hasFilter(type, p)) {
          this.addFilter(type, p);
        }
      });
    },

    showUsageModal() {
      this.$refs.usageModal.show();
    },
  },
};
</script>

<style scoped>

</style>
