<template>
  <content-card heading="Configure Daily Channel">
    <div v-if="fetchingMutations">
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
      <common-configuration
        default-path="storage/logs/laravel.log"
        channel-name="Daily"
        input-path="Logging/Channels/Daily/Path"
        input-mutation-path="logging/channels/daily/path"
        level="debug"
        level-path="logging/channels/daily/level"
        @loaded="commonConfigLoaded = true"
      />

      <row push20>
        <column
          size="6"
          offset="3"
        >
          <pg-labeled-input
            v-model="days"
            label="Days"
            @input="persistDays"
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
import ContentCard from '@/components/Cards/ContentCard';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import CommonConfiguration from '@/components/Scaffolding/Logging/Channels/Common/CommonConfiguration';

export default {
  name: 'DailyChannel',
  components: {
    CommonConfiguration,
    PgLabeledInput,
    IndeterminateProgressBar,
    Column,
    ContentCard,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      days: 14,
    };
  },
  async created() {
    this.registerMutable(
      'Daily Channel Path',
      'logging/channels/daily/days',
      {
        then: (value) => this.days = value || this.days,
      },
    );
  },
  methods: {
    persistDays() {
      const payload = {
        name: 'Path',
        path: 'logging/channels/daily/days',
        value: this.days,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped></style>
