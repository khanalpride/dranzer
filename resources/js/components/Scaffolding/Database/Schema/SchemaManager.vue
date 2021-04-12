<template>
    <scaffolding-component-container heading="Schema Manager">
        <initializing-progress-container
                no-centered-text
                :initializing="loading"/>
        <tabs-manager ref="blueprintTabsManager"
                      path="config/tabs/blueprint/active"
                      removable
                      ellipse-heading
                      :heading-callback="getTabHeading"
                      :tabs="tabs"
                      @click="onTabClick"
                      @remove="onTabRemove"
                      v-if="hasBlueprints && tabs.length && !loading">
            <template :slot="blueprint.id" v-for="blueprint in visibleBlueprints">
                <blueprint :key="blueprint.id"
                           :blueprint-id="blueprint.id"
                           :blueprints="visibleBlueprints"
                           :raw-blueprints="allBlueprints"
                           :persisted="blueprint"
                           :mandatory-model="isUserBlueprint(blueprint)"
                           :can-edit-model-name="!isUserBlueprint(blueprint)"
                           :can-edit-table-name="!isUserBlueprint(blueprint)"
                           @create-model-toggled="onCreateModelToggled($event, blueprint)"
                           @sync="onSyncBlueprint"/>
            </template>
        </tabs-manager>
        <content-container centered v-if="!hasBlueprints && !loading">
            <button class="btn btn-primary" @click="addNewBlueprint">
                <i class="fa fa-plus"></i>
                Add Blueprint
            </button>
        </content-container>
    </scaffolding-component-container>
</template>

<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import TabsManager from '@/components/Tabs/TabsManager';
import Blueprint from '@/components/Scaffolding/Database/Schema/Blueprint';
import InitializingProgressContainer from '@/components/Scaffolding/Containers/Progress/InitializingProgressContainer';
import ContentContainer from '@/components/Content/ContentContainer';
import { mapState } from 'vuex';

export default {
  name: 'SchemaManager',
  mixins: [asyncImports, mutations],
  components: {
    ContentContainer,
    InitializingProgressContainer,
    Blueprint,
    TabsManager,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      blueprints: [],

      deleting: [],

      createModels: {},
    };
  },
  computed: {
    ...mapState('project', ['project']),

    tabs() {
      return this.visibleBlueprints.filter((s) => !s.placeholder).concat([
        {
          id: `P${Math.round(Math.random() * Number.MAX_SAFE_INTEGER)}`,
          label: '<span class="text-green"><i class="fa fa-plus small"></i> New Blueprint</span>',
          placeholder: true,
          persistable: false,
          removable: false,
          ellipseHeading: false,
        },
      ]);
    },

    hasBlueprints() {
      return this.tabs.filter((s) => !s.placeholder).length > 0;
    },

    allBlueprints() {
      return this.blueprints.map((blueprint) => ({ ...blueprint, removable: !this.isUserBlueprint(blueprint) }));
    },

    visibleBlueprints() {
      return this.allBlueprints.filter((s) => s.visible !== false);
    },
  },
  async created() {
    this.loading = true;

    const { data } = await this.mutation({ path: 'database/blueprints/', like: true, refresh: true });

    this.blueprints = this.getPersistedMutationValue(data) || [];

    if (!this.blueprints.length) {
      this.addNewBlueprint(true);
    }

    this.loading = false;
  },
  methods: {
    addNewBlueprint(activate = true) {
      const blueprint = {
        id: `B${Math.round(Math.random() * Number.MAX_SAFE_INTEGER)}`,
        modelName: '',
        tableName: '',
        columns: [],
        removable: true,
      };

      this.blueprints.push(blueprint);

      if (activate) {
        this.$nextTick(() => {
          this.$refs.blueprintTabsManager.activateTabByIndex(this.visibleBlueprints.length - 1);
        });
      }

      return blueprint;
    },

    isUserBlueprint(blueprint) {
      return blueprint.id.substr(0, 13) === 'UserBlueprint';
    },

    getTabHeading(tab) {
      if (tab.label) {
        return tab.label;
      }

      const modelName = tab.modelName ? tab.modelName.trim() : '';
      const tableName = tab.tableName ? tab.tableName.trim() : '';

      if (modelName !== '') {
        return modelName;
      }

      if (tableName !== '') {
        return tableName;
      }

      return 'Unnamed Blueprint';
    },

    onCreateModelToggled(checked, blueprint) {
      blueprint.createModel = checked;
    },

    onSyncBlueprint(blueprint) {
      const blueprintIndex = this.blueprints.findIndex((s) => s.id === blueprint.id);
      if (blueprintIndex > -1) {
        this.blueprints[blueprintIndex].modelName = blueprint.modelName;
        this.blueprints[blueprintIndex].tableName = blueprint.tableName;
        this.blueprints[blueprintIndex].columns = blueprint.columns;
      }
    },

    onTabClick(tab) {
      if (tab.placeholder) {
        this.addNewBlueprint();
      }
    },

    async onTabRemove(blueprintId) {
      const blueprintIndex = this.visibleBlueprints.findIndex((s) => s.id === blueprintId);
      if (blueprintIndex > -1) {
        await this.deleteMutation(`database/blueprints/${blueprintId}`, {
          then: () => {
            this.blueprints.splice(blueprintIndex, 1);
            this.$refs.blueprintTabsManager.activateNextTab(blueprintIndex);
            this.deleteMutation(`config/database/blueprints/${blueprintId}`);
            this.deleteMutation(`database/relations/${blueprintId}/*`);
          },
        });
      }
    },
  },
};
</script>

<style scoped></style>
