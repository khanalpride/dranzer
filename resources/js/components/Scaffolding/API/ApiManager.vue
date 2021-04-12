<template>
  <scaffolding-component-container heading="Manage API" :loading="loading || fetchingMutations">
    <row>
     <column centered>
       <pg-check-box v-model="config.generate"
                     no-margin
                     centered
                     label="Build API" />
     </column>

      <column centered v-if="config.generate">
        <separator />
        <pg-check-box v-model="config.jwtAuth"
                      no-margin
                      centered
                      label="Authenticate Using JWT"
                      @change="onJWTAuthStateToggled"/>
        <pg-check-box v-model="config.sanctumAuth"
                      no-margin
                      centered
                      label="Authenticate Using Laravel Sanctum"
                      @change="onSanctumAuthStateToggled" />
      </column>

      <column centered v-if="config.generate">
        <separator />
        <p class="text-center text-info no-margin">
          <external-link url="https://github.com/tailflow/laravel-orion" bold color="complete">Laravel Orion</external-link>
          <span class="hint-text">is used to generate API resources.</span>
        </p>
        <separator />
        <row>
          <column size="4" offset="4">
            <selectable-models :value="config.selectedModels"
                               :models="models"
                               @change="onSelectedModelsChanged" />
          </column>
        </row>

        <row v-if="config.selectedModels.length">
          <column>
            <basic-content-section heading="API Configuration" prepend-separator>
              <tabs-manager ref="modelTabsManager"
                            :tabs="modelTabs"
                            path="config/api/tabs/models/active"
                            v-bind:tab.sync="activeModelTab">
                <template :slot="tab.id" v-for="tab in modelTabs">
                  <orion-api-resource :model="getModelById(tab.id)" :key="tab.id" />
                </template>
              </tabs-manager>
            </basic-content-section>
          </column>
        </row>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import TabsManager from '@/components/Tabs/TabsManager';
import sharedMutations from '@/mixins/shared_mutations';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import BasicContentSection from '@/components/Content/BasicContentSection';
import OrionApiResource from '@/components/Scaffolding/API/Orion/OrionApiResource';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import ExternalLink from '@/components/Navigation/ExternalLink';
import SelectableModels from '@/components/Select/SelectableModels';

export default {
  name: 'ApiManager',
  mixins: [asyncImports, mutations, sharedMutations],
  components: {
    SelectableModels,
    ExternalLink,
    OrionApiResource,
    TabsManager,
    BasicContentSection,
    Separator,
    PgCheckBox,
    Row,
    Column,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      blueprints: [],

      config: {
        selectedModels: [],
        generate: false,
        jwtAuth: false,
        sanctumAuth: true,
      },

      activeModelTab: null,
    };
  },
  watch: {
    config: {
      handler(v) {
        const payload = {
          name: 'API Config',
          path: 'api',
          value: v,
        };

        this.mutate(payload);
      },
      deep: true,
    },
  },
  computed: {
    models() {
      return this.blueprints.filter((s) => s.modelName && s.modelName.trim() !== '');
    },

    modelTabs() {
      return this.blueprints.filter((s) => this.config.selectedModels.includes(s.id)).map((s) => ({
        id: s.id,
        label: s.modelName,
      }));
    },
  },
  async created() {
    this.loading = true;
    await this.assignBlueprints();
    await this.sync();

    this.$nextTick(() => {
      this.loading = false;
    });
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'api' });
      this.config = data.value || this.config;
    },

    getModelColumnTabs(tab) {
      const model = this.blueprints.find((s) => s.id === tab.id);

      return model ? model.columns.map((c) => ({ id: `C${c.id}`, name: c.name })) : [];
    },

    getModelById(modelId) {
      return this.blueprints.find((s) => s.id === modelId);
    },

    onSelectedModelsChanged(models) {
      const activeTab = this.modelTabs.find((m) => m.id === this.activeModelTab);

      const addedModelId = models.find((m) => !this.config.selectedModels.find((s) => s === m));

      const deletedModelId = this.config.selectedModels.find((s) => !models.includes(s));

      this.config.selectedModels = models;

      if (deletedModelId) {
        this.deleteMutation(`api/resources/${deletedModelId}`);
      }

      this.$nextTick(() => {
        const newTabIndex = this.modelTabs.findIndex((m) => m.id === addedModelId);

        if (!models.includes(activeTab) && this.$refs.modelTabsManager) {
          this.$refs.modelTabsManager.activateTabByIndex(newTabIndex > -1 ? newTabIndex : this.modelTabs.length - 1);
        }
      });
    },

    onJWTAuthStateToggled(active) {
      if (active) {
        this.config.sanctumAuth = false;
      }
    },

    onSanctumAuthStateToggled(active) {
      if (active) {
        this.config.jwtAuth = false;
      }
    },
  },
};
</script>

<style scoped>

</style>
