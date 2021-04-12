<template>
  <content-card heading="Configure Stack Channel">
    <div v-if="fetchingMutations || loading">
      <row>
        <column
          size="4"
          offset="4"
        >
          <p class="text-center">
            Restoring Configuration...
          </p>
          <indeterminate-progress-bar />
        </column>
      </row>
    </div>

    <div v-else>
      <row>
        <column centered>
          <p class="text-primary">
            Channels
          </p>
        </column>

        <column
          size="6"
          offset="3"
          centered
        >
          <el-select
            v-model="channels"
            multiple
            collapse-tags
            filterable
            @change="persistChannels"
          >
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
              label="ErrorLog"
              value="errorlog"
            />
            <el-option
              label="Monolog"
              value="monolog"
            />
          </el-select>
        </column>

        <column push10>
          <separator />
        </column>

        <column centered>
          <pg-check-box
            v-model="ignoreExceptions"
            centered
            label="Ignore Exceptions"
            @change="persistOptions"
          />
        </column>
      </row>
    </div>
  </content-card>
</template>

<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import ContentCard from '@/components/Cards/ContentCard';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'StackChannel',
  components: {
    Separator,
    PgCheckBox,
    IndeterminateProgressBar,
    Column,
    ContentCard,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,

      channels: ['single'],

      ignoreExceptions: false,
    };
  },
  async created() {
    this.loading = true;

    await this.syncChannels();
    await this.syncOptions();

    this.loading = false;
  },
  methods: {
    async syncChannels() {
      const { data } = await this.mutation({
        path: 'logging/channels/config/stack/channels',
      });
      this.channels = data.value || this.channels;
    },

    async syncOptions() {
      const { data } = await this.mutation({
        path: 'logging/channels/config/stack/options',
      });
      this.ignoreExceptions = data.value && data.value.ignoreExceptions !== undefined
        ? data.value.ignoreExceptions
        : false;
    },
    persistChannels() {
      const name = 'Stack Channels';
      const path = 'logging/channels/config/stack/channels';
      const value = this.channels;

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },

    persistOptions() {
      const name = 'Stack Options';
      const path = 'logging/channels/config/stack/options';
      const value = { ignoreExceptions: this.ignoreExceptions };

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped></style>
