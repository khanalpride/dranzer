<template>
  <content-card heading="Configure Slack Channel">
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
        <column
          size="6"
          offset="3"
          centered
        >
          <p class="text-primary">
            Logging Level
          </p>
          <level-selector
            :default="config.level"
            @change="onLevelChanged"
          />
        </column>

        <column>
          <separator />
        </column>

        <column
          size="8"
          offset="2"
        >
          <row>
            <column>
              <pg-labeled-input
                v-model="config.webhookURL"
                label="Webhook URL"
              />
            </column>

            <column size="6">
              <pg-labeled-input
                v-model="config.username"
                label="Username"
                placeholder="Laravel Log"
              />
            </column>

            <column size="6">
              <pg-labeled-input
                v-model="config.emoji"
                label="Emoji"
                placeholder=":boom:"
              />
            </column>
          </row>
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
import Separator from '@/components/Layout/Separator';
import ContentCard from '@/components/Cards/ContentCard';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import LevelSelector from '@/components/Scaffolding/Logging/Channels/Common/LevelSelector';

export default {
  name: 'SlackChannel',
  components: {
    PgLabeledInput,
    Separator,
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

      config: {
        webhookURL: '',
        username: 'Laravel Log',
        emoji: ':boom:',
        level: 'critical',
      },
    };
  },
  watch: {
    config: {
      handler() {
        this.persist();
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;

    const { data } = await this.mutation({
      path: 'logging/channels/slack',
    });

    this.config = data.value || this.config;

    this.loading = false;
  },
  methods: {
    persist() {
      const name = 'Slack Logging Config';
      const path = 'logging/channels/slack';

      const payload = {
        name,
        path,
        value: this.config,
      };

      this.mutate(payload);
    },

    onLevelChanged(level) {
      this.config.level = level;

      this.$nextTick(() => {
        this.persist();
      });
    },
  },
};
</script>

<style scoped></style>
