<template>
  <scaffolding-component-container
    heading="Configure Logging Channels"
    :loading="loading || fetchingMutations"
  >
    <row>
      <column centered>
        <p class="text-primary">
          Default Log Channel
        </p>
      </column>

      <column
        size="4"
        offset="4"
        centered
      >
        <el-select
          v-model="channel"
          filterable
          @change="defaultChannelChanged"
        >
          <el-option
            label="Stack"
            value="stack"
          />
          <el-option
            label="Single"
            value="single"
          />
          <el-option
            label="Daily"
            value="daily"
          />
          <el-option
            label="Slack"
            value="slack"
          />
          <el-option
            label="Syslog"
            value="syslog"
          />
          <el-option
            label="Errorlog"
            value="errorlog"
          />
          <el-option
            label="Monolog"
            value="monolog"
          />
        </el-select>
      </column>

      <column>
        <separator />
      </column>

      <column
        size="10"
        offset="1"
      >
        <el-tabs
          v-model="activeTab"
          @tab-click="persistActiveTab"
        >
          <el-tab-pane
            v-for="tab in tabs"
            :key="tab.label"
            :label="tab.label"
            :name="tab.label"
          >
            <component
              :is="tab.component"
              v-if="activeTab === tab.label"
            />
          </el-tab-pane>
        </el-tabs>
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
import ContentCard from '@/components/Cards/ContentCard';

import StackChannel from '@/components/Scaffolding/Logging/Channels/StackChannel';
import SingleChannel from '@/components/Scaffolding/Logging/Channels/SingleChannel';
import DailyChannel from '@/components/Scaffolding/Logging/Channels/DailyChannel';
import SlackChannel from '@/components/Scaffolding/Logging/Channels/SlackChannel';
import SyslogChannel from '@/components/Scaffolding/Logging/Channels/SyslogChannel';
import ErrorLogChannel from '@/components/Scaffolding/Logging/Channels/ErrorLogChannel';

import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import MonologChannel from '@/components/Scaffolding/Logging/Channels/MonologChannel';

export default {
  name: 'LoggingManager',
  components: {
    ScaffoldingComponentContainer,
    Separator,
    IndeterminateProgressBar,
    Column,
    ContentCard,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,

      channel: 'stack',

      activeTab: 'Stack',

      tabs: [
        {
          label: 'Stack',
          component: StackChannel,
        },
        {
          label: 'Single',
          component: SingleChannel,
        },
        {
          label: 'Daily',
          component: DailyChannel,
        },
        {
          label: 'Slack',
          component: SlackChannel,
        },
        {
          label: 'Syslog',
          component: SyslogChannel,
        },
        {
          label: 'Errorlog',
          component: ErrorLogChannel,
        },
        {
          label: 'Monolog',
          component: MonologChannel,
        },
      ],
    };
  },
  async created() {
    this.loading = true;

    await this.syncDefaultChannel();
    await this.syncActiveTab();

    this.loading = false;
  },
  methods: {
    async syncDefaultChannel() {
      const { data } = await this.mutation({
        path: 'logging/channels/default',
      });
      this.channel = data.value || this.channel;
    },

    async syncActiveTab() {
      const { data } = await this.mutation({
        path: 'logging/tabs/channels/active',
      });
      this.activeTab = data.value || this.activeTab;
    },

    persistDefaultChannel() {
      const name = 'Default Log Channel';
      const path = 'logging/channels/default';
      const value = this.channel;

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },

    persistActiveTab(tab) {
      this.$nextTick(() => {
        const name = 'Active Log Channel Tab';
        const path = 'logging/tabs/channels/active';
        const value = tab.name;

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    defaultChannelChanged() {
      this.activeTab = this.channel.substr(0, 1).toUpperCase()
        + this.channel.substr(1);
      this.persistActiveTab({ name: this.activeTab });
      this.persistDefaultChannel();
    },
  },
};
</script>

<style scoped></style>
