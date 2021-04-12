<template>
  <content-card heading="Configure Syslog Channel">
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
        <column>
          <column
            size="6"
            offset="3"
            centered
          >
            <p class="text-primary">
              Logging Level
            </p>
            <level-selector
              :default="level"
              @change="persistLevel"
            />
          </column>
        </column>
      </row>
    </div>
  </content-card>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ContentCard from '@/components/Cards/ContentCard';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import LevelSelector from '@/components/Scaffolding/Logging/Channels/Common/LevelSelector';

export default {
  name: 'SyslogChannel',
  components: {
    LevelSelector,
    IndeterminateProgressBar,
    Column,
    ContentCard,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,
      level: 'debug',
    };
  },
  async created() {
    this.loading = true;

    const { data } = await this.mutation({
      path: 'logging/channels/syslog/level',
    });
    this.level = data.value || 'debug';

    this.loading = false;
  },
  methods: {
    persistLevel(level) {
      const name = 'Syslog Logging Level';
      const path = 'logging/channels/syslog/level';

      const payload = {
        name,
        path,
        value: level,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped></style>
