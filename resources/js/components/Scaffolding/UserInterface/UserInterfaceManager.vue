<template>
  <scaffolding-component-container heading="Manage User Interface" :loading="loading || fetchingMutations">
    <row>
      <column centered>
        <pg-check-box no-margin centered v-model="enabled" label="Create User Interface" @change="persistIsEnabled"/>
      </column>
    </row>

    <basic-content-section heading="Layout Providers" prepend-separator v-if="enabled">
      <row>
        <column centered>
          <pg-check-box no-margin centered label="Admin" :value="hasAdminLayout" @change="onToggleLayout($event, 'admin')"/>
          <pg-check-box no-margin centered label="Custom" :value="hasCustomLayout" @change="onToggleLayout($event, 'blade')"/>
        </column>

        <column v-if="layouts.length">
          <row>
            <column>
              <separator/>
            </column>

            <column>
              <tabs-manager ref="layoutTabsManager"
                            path="ui/settings/tabs/active"
                            :tabs="layoutTabs"
                            @removed="onRemoveLayoutTab">
                <template :slot="layout.id" v-for="layout in layoutTabs">
                  <component :key="layout.id" :is="layout.component" v-bind="layout.props"
                             @updated="handleLayoutUpdates"/>
                </template>
              </tabs-manager>
            </column>
          </row>
        </column>
      </row>
    </basic-content-section>
  </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import ContentCard from '@/components/Cards/ContentCard';
import Column from '@/components/Layout/Grid/Column';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import Separator from '@/components/Layout/Separator';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import TabsManager from '@/components/Tabs/TabsManager';
import { mapGetters } from 'vuex';
import BasicContentSection from '@/components/Content/BasicContentSection';
import BladeLayoutBuilder from '@/components/Scaffolding/UserInterface/Custom/Blade/BladeLayoutBuilder';
import BladeHelpers from '@/helpers/blade_helpers';
import OrchidLayoutManager from '@/components/Scaffolding/UserInterface/Orchid/OrchidLayoutManager';
import LockedLayoutBuilder from '@/components/Scaffolding/UserInterface/Custom/Blade/Partials/LockedLayoutBuilder';

const { axios } = window;

export default {
  name: 'UserInterfaceManager',
  mixins: [asyncImports, mutations],
  components: {
    BasicContentSection,
    TabsManager,
    PgCheckBox,
    ScaffoldingComponentContainer,
    Separator,
    IndeterminateProgressBar,
    Column,
    ContentCard,
    Row,
  },
  data() {
    return {
      loading: false,

      enabled: false,

      layouts: [],

      activeLayoutTab: 'blade',

      removing: [],
    };
  },
  computed: {
    ...mapGetters('project', ['projectId']),

    layoutTabs() {
      return this.layouts.map((layout) => {
        const tab = layout;

        if (layout.type === 'blade') {
          if (this.project.downloaded) {
            tab.component = LockedLayoutBuilder;
          } else {
            tab.component = BladeLayoutBuilder;
          }
        } else {
          tab.component = OrchidLayoutManager;
        }

        tab.props = {
          persisted: layout,
        };

        tab.label = this.getTabLabel(tab);

        tab.closable = layout.closable || true;

        tab.disabled = layout.disabled || false;

        return tab;
      }).sort((a, b) => (a.type === 'blade' || b.type !== 'blade' ? 1 : -1));
    },

    hasCustomLayout() {
      return this.layouts.find((layout) => layout.type === 'blade');
    },

    hasAdminLayout() {
      return this.layouts.find((layout) => layout.type === 'admin');
    },

  },
  async created() {
    this.loading = true;

    await this.syncIsEnabled();

    if (this.enabled) {
      await this.syncLayouts();
    }

    this.loading = false;
  },
  methods: {
    async syncIsEnabled() {
      const { data } = await this.mutation({ path: 'ui/settings/enabled' });

      this.enabled = data.value !== null ? data.value : this.enabled;
    },

    async persistIsEnabled() {
      const name = 'UI';
      const path = 'ui/settings/enabled';
      const value = this.enabled;

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);

      if (value) {
        this.loading = true;
        await this.syncLayouts();
        this.loading = false;
      }
    },

    async syncLayouts() {
      const { data } = await this.mutation({ path: 'ui/layouts/', like: true, refresh: true });

      this.layouts = data.value ? data.value.map((v) => v.value) : [];
    },

    addLayout(type) {
      const layout = {
        id: `L${Math.round(Math.random() * Number.MAX_SAFE_INTEGER)}`,
        name: type === 'blade' ? 'unnamed_layout' : 'Admin Layout',
        path: this.getDefaultLayoutPath(type),
        type,
      };

      if (type === 'blade') {
        layout.strict = true;
        layout.partials = [];
      }

      this.layouts.push(layout);

      const value = {
        id: layout.id,
        name: layout.name,
        path: layout.path,
        type,
      };

      const payload = {
        name: 'Layout',
        path: `ui/layouts/${layout.id}`,
        value,
      };

      this.mutate(payload);
    },

    getDefaultLayoutPath(type) {
      return type === 'blade' ? `layouts/${BladeHelpers.bladeTemplateFilename('unnamed_layout')}` : null;
    },

    async handleBladeTemplateUpdate(layout) {
      const layoutIndex = this.layouts.findIndex((l) => l.id === layout.id);

      if (layoutIndex > -1) {
        const updatedLayout = {
          id: layout.id,
          name: layout.name,
          path: layout.path,
          strict: layout.strict,
          partials: layout.partials,
          type: 'blade',
        };

        this.layouts[layoutIndex] = layout;

        const layoutUpdatePayload = {
          name: 'Layout',
          path: `ui/layouts/${layout.id}`,
          value: updatedLayout,
        };

        this.mutate(layoutUpdatePayload);

        // Update the layout details for the master theme.
        const { data } = await this.mutation({ path: 'assets/template/theme/master' });

        if (data.value && data.value.layoutId && data.value.layoutId === layout.id) {
          const newValue = data.value;

          newValue.layoutName = layout.name;

          const payload = {
            name: 'Master Theme',
            path: 'assets/template/theme/master',
            value: newValue,
          };

          this.mutate(payload);
        }
      }
    },

    isRemovingTab(tab) {
      return this.removing.indexOf(tab.id) > -1;
    },

    handleLayoutUpdates(layout) {
      if (layout.type === 'blade') {
        this.handleBladeTemplateUpdate(layout);
      }
    },

    async removeLayout(layoutId) {
      const layoutIndex = this.layouts.findIndex((layout) => layout.id === layoutId);

      if (layoutIndex > -1) {
        const layout = this.layouts[layoutIndex];
        layout.disabled = true;

        const { status } = await this.deleteMutation(`ui/layouts/${layoutId}`);

        if (status === 201 || status === 204) {
          this.layouts.splice(layoutIndex, 1);
          this.$refs.layoutTabsManager.activateNextTab(layoutIndex);

          if (layout.type === 'blade') {
            const { data } = await this.mutation({ path: 'assets/template/theme/master' });

            if (data.value && data.value.layoutId && data.value.layoutId === layoutId) {
              const payload = {
                name: 'Master Theme',
                path: 'assets/template/theme/master',
                value: null,
              };

              this.mutate(payload);

              await axios.post('/assets/delete', {
                key: layoutId,
                module: 'layout',
                projectId: this.projectId,
              });
            }
          }
        }
      }
    },

    getTabLabel(layout) {
      return layout.type === 'blade' ? 'Custom Layout' : 'Admin Layout';
    },

    onToggleLayout(checked, type) {
      if (checked) {
        this.addLayout(type);
        this.$nextTick(() => {
          if (this.$refs.layoutTabsManager) {
            this.$refs.layoutTabsManager.activateTabByIndex(type === 'admin' ? 0 : this.layouts.length - 1);
          }
        });
      } else {
        const layout = this.layouts.find((l) => l.type === type);
        if (layout) {
          this.removeLayout(layout.id);
        }
      }
    },

    onRemoveLayoutTab(layoutId) {
      this.removeLayout(layoutId);
    },
  },
};
</script>

<style scoped>

</style>
