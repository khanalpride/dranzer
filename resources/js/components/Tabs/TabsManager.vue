<template>
  <div>
    <row>
      <column v-if="loading" size="3">
        <p class="text-black">Restoring Module...</p>
        <indeterminate-progress-bar />
      </column>
      <column v-else>
        <el-tabs
          :value="activeTab"
          :tab-position="direction"
          @tab-click="onTabClick"
          @tab-remove="onTabRemove"
        >
          <el-tab-pane
            v-for="tab in tabs"
            :key="tab.id"
            :name="tab.id"
            :closable="isTabRemovable(tab)"
            :disabled="disabled || isTabDisabled(tab)"
          >
            <span
              slot="label"
              :class="{
                'default-tab': !isTabDisabled(tab),
                'disabled-tab': isTabDisabled(tab),
              }"
              v-html="getTabHeading(tab)"
            />
            <slot v-if="activeTab === tab.id && !tab.placeholder" :name="tab.id" />
          </el-tab-pane>
        </el-tabs>
      </column>
    </row>
  </div>
</template>

<script>
import mutations from '@/mixins/mutations';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import { mapState } from 'vuex';

export default {
  name: 'TabsManager',
  components: { IndeterminateProgressBar, Column, Row },
  mixins: [mutations],
  props: {
    tabs: {
      type: Array,
      required: true,
    },
    headingCallback: Function,
    path: {
      type: String,
      default: null,
    },
    direction: {
      type: String,
      default: 'top',
    },
    removable: Boolean,
    ellipseHeading: Boolean,
    disabled: Boolean,
    noImplicitDisable: Boolean,
  },
  data() {
    return {
      loading: true,
      activeTab: null,

      removing: [],
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  async created() {
    this.loading = true;
    await this.syncActiveTab();
    this.loading = false;

    this.$nextTick(() => {
      this.$emit('update:tab', this.activeTab);
    });
  },
  methods: {
    async syncActiveTab() {
      if (!this.path) {
        this.setDefaultActiveTab();
        return;
      }

      const { data } = await this.mutation({ path: this.path });

      const activeTab = data.value;

      if (!this.tabs.find((t) => t.id === activeTab)) {
        this.setDefaultActiveTab();
        this.persistActiveTab();
        return;
      }

      this.activeTab = activeTab;
    },

    activateNextTab(activeIndex) {
      let newIndex;

      if (activeIndex > 0 && this.tabs.length) {
        newIndex = activeIndex - 1;
      } else if (activeIndex === 0 && this.tabs.length) {
        newIndex = 0;
      } else {
        newIndex = 1;
      }

      if (newIndex > -1) {
        this.$nextTick(() => {
          this.activeTab = this.tabs[newIndex].id;
          this.persistActiveTab();
        });
      }
    },

    activateTabByIndex(index) {
      if (index < 0 || index > this.tabs.length - 1) {
        return;
      }

      this.activeTab = this.tabs[index].id;
      this.persistActiveTab();
    },

    getTabHeading(tab) {
      const heading = this.headingCallback ? this.headingCallback(tab) : (tab.label || tab.title || tab.name);
      return !this.ellipseHeading || tab.ellipseHeading === false ? heading : this.str.ellipse(heading, 12);
    },

    setDefaultActiveTab() {
      this.activeTab = this.tabs && this.tabs.length ? this.tabs[0].id : null;
    },

    persistActiveTab() {
      if (!this.path) {
        return;
      }

      this.mutate({
        name: 'Active Tab',
        value: this.activeTab,
        path: this.path,
      });
    },

    isRemovingTab(tab) {
      return this.removing.indexOf(tab.id) > -1;
    },

    onTabClick(e) {
      const tabName = e.name;

      if (!tabName) {
        return;
      }

      const tab = this.tabs.find((t) => t.id === tabName);

      this.$emit('click', tab);

      if (tabName === this.activeTab) {
        return;
      }

      this.$emit('update:tab', tab.id);

      this.activeTab = tabName;

      if (tab.persistable === undefined || tab.persistable) {
        this.persistActiveTab();
      }
    },

    onTabRemove(tabName) {
      if (this.project && this.project.downloaded && !this.noImplicitDisable) {
        return;
      }

      this.$emit('remove', tabName);
    },

    isTabDisabled(tab) {
      return tab.disabled && tab.disabled === true;
    },

    isTabRemovable(tab) {
      return tab.removable !== undefined ? tab.removable === true : this.removable;
    },
  },
};
</script>

<!--suppress CssUnusedSymbol -->
<style scoped>
.defaultTab {
  color: #409eff !important;
}

.disabled-tab {
  color: grey !important;
}
</style>
