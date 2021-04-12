<template>
  <scaffolding-component-container heading="Configure Admin Panel" :loading="loading || fetchingMutations">
    <row>
      <column>
        <fill-in-modal ref="processingOrchidModuleModal">
          <row>
            <column>
              <p class="text-primary hint-text">Processing...</p>
              <indeterminate-progress-bar />
            </column>
          </row>
        </fill-in-modal>
      </column>
    </row>
    <row>
      <column size="4" offset="4">
        <form-input-title title="Sidebar Navigation Sections" />
        <simple-select class="el-sel-full-width" :entities="sections"
                       :value="sectionNames"
                       multiple
                       collapse-tags
                       filterable
                       allow-create
                       @change="onSectionsToggled">
          <template slot-scope="{ entity }">
            <el-option :label="entity.name" :value="entity.name" :key="entity.id" />
          </template>
        </simple-select>
      </column>
      <column push5 size="4" offset="4">
        <form-input-title title="Modules" />
        <simple-select ref="moduleSelector"
                       placeholder="Select modules..."
                       :entities="modules"
                       v-model="selectedModules"
                       multiple
                       collapse-tags
                       full-width
                       filterable
                       @change="onModuleToggled">
          <template slot-scope="{ entity }">
            <el-option :label="entity.modelName" :value="entity.id" :key="entity.id">
              <span>{{ entity.modelName }}</span>
              <span class="pull-right m-r-20">{{ entity.index }}</span>
            </el-option>
          </template>
        </simple-select>
      </column>
      <column push10 v-if="selectedModules.length">
        <tabs-manager ref="adminModulesTabManager" :tabs="renderableModules" path="config/ui/admin/modules/tabs/active">
          <template :slot="renderable.id" v-for="renderable in renderableModules">
            <orchid-module :key="renderable.id"
                           :model="renderable"
                           :sections="sections"
                           :blueprints="rawBlueprints"
                           :eloquent-relations="eloquentRelations"/>
          </template>
        </tabs-manager>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import sharedMutations from '@/mixins/shared_mutations';
import mutations from '@/mixins/mutations';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import TabsManager from '@/components/Tabs/TabsManager';
import OrchidModule from '@/components/Scaffolding/UserInterface/Orchid/Components/OrchidModule';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import FillInModal from '@/components/Modals/FillInModal';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import SimpleSelect from '@/components/Select/SimpleSelect';

export default {
  name: 'OrchidLayoutManager',
  components: {
    SimpleSelect,
    IndeterminateProgressBar,
    FillInModal,
    FormInputTitle,
    OrchidModule,
    TabsManager,
    Column,
    Row,
    ScaffoldingComponentContainer,
  },
  mixins: [mutations, sharedMutations],
  data() {
    return {
      loading: false,

      rawBlueprints: [],

      blueprints: [],

      selectedModules: [],

      persistedModules: [],

      sections: [],

      eloquentRelations: [],

      fullTextSearch: true,

      previouslySelectedModules: [],
    };
  },
  computed: {
    modules() {
      return this.blueprints.filter((blueprint) => blueprint.modelName && blueprint.modelName.trim() !== '' && blueprint.visible !== false);
    },

    sectionNames() {
      return this.sections.map((s) => s.name);
    },

    renderableModules() {
      return this.modules.filter(
        (s) => this.selectedModules.includes(s.id),
      ).map(
        (s) => ({
          ...s,
          label: s.modelName,
        }),
      );
    },
  },
  async created() {
    this.loading = true;
    await this.syncSelectedModules();
    await this.syncSections();
    await this.syncEloquentRelations();

    let blueprints = await this.fetchBlueprints();

    this.rawBlueprints = blueprints;

    // Add an index property so we know the display priority.
    blueprints = blueprints.filter((s) => s.modelName !== 'User').map((s, i) => {
      const persisted = this.persistedModules.find((m) => s.id === m.id);
      return {
        ...s,
        index: s.index || persisted ? persisted.index : i,
        removable: false,
      };
    });

    this.blueprints = blueprints;

    this.loading = false;

    this.previouslySelectedModules = this.selectedModules;

    // Add a default section...
    if (!this.sections.length) {
      this.sections = ['Resources'].map((s) => ({ id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER), name: s }));
      this.persistSections();
    }
  },
  methods: {
    async syncSections() {
      const { data } = await this.mutation({ path: 'config/ui/admin/sections' });
      this.sections = data.value || [];
    },

    async syncSelectedModules() {
      const { data } = await this.mutation({ path: 'config/ui/admin/modules' });
      this.persistedModules = data.value || [];
      this.selectedModules = (data.value || []).filter((s) => s.selected).map((v) => v.id);
    },

    async syncEloquentRelations() {
      const { data } = await this.mutation({ path: 'eloquent/relations/regular', like: true, refresh: true });
      this.eloquentRelations = this.getPersistedMutationValue(data);
    },

    persistSections() {
      const payload = {
        name: 'Admin Sidebar Sections',
        path: 'config/ui/admin/sections',
        value: this.sections,
      };

      this.mutate(payload);
    },

    async onModuleToggled(modules) {
      if (this.$refs.processingOrchidModuleModal) {
        this.$refs.processingOrchidModuleModal.show();
      }

      const { previouslySelectedModules } = this;

      const removed = previouslySelectedModules.find((m) => !modules.includes(m));

      if (removed) {
        await this.deleteMutation(`ui/admin/modules/${removed}`);
      }

      const newSelection = modules.find((m) => !previouslySelectedModules.includes(m));

      this.previouslySelectedModules = modules;

      const indexed = [];

      let nonIndexableIndex = modules.length;

      for (let i = 0; i < this.blueprints.length; i += 1) {
        const blueprint = this.blueprints[i];
        const { id } = blueprint;
        if (modules.includes(id)) {
          const mIndex = modules.findIndex((m) => m === id);
          this.blueprints[i].index = mIndex;
          indexed.push({ index: mIndex, id, selected: true });
        } else {
          indexed.push({ index: nonIndexableIndex, id, selected: false });
          this.blueprints[i].index = nonIndexableIndex;
          nonIndexableIndex += 1;
        }
      }

      const payload = {
        name: 'Admin Modules',
        path: 'config/ui/admin/modules',
        value: indexed,
      };

      this.mutate(payload);

      this.$nextTick(async () => {
        if (this.renderableModules.length) {
          if (newSelection) {
            const selectionIndex = this.renderableModules.findIndex((r) => r.id === newSelection);
            if (selectionIndex > -1) {
              if (this.$refs.adminModulesTabManager) {
                this.$refs.adminModulesTabManager.activateTabByIndex(selectionIndex);
              }
            }
          } else if (this.$refs.adminModulesTabManager) {
            this.$refs.adminModulesTabManager.activateTabByIndex(this.renderableModules.length - 1);
          }
        }

        if (this.$refs.moduleSelector) {
          if (this.$refs.moduleSelector.$children && this.$refs.moduleSelector.$children.length) {
            this.$refs.moduleSelector.$children[0].blur();
          }
        }

        await this.promises.sleep(2500);

        if (this.$refs.processingOrchidModuleModal) {
          this.$refs.processingOrchidModuleModal.hide();
        }
      });
    },

    onSectionsToggled(sections) {
      this.sections = sections.map((s) => ({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        name: s,
      }));

      this.persistSections();
    },
  },
};
</script>

<style scoped>

</style>
