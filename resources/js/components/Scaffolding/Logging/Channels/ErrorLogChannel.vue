<template>
  <content-card heading="Configure ErrorLog Channel">
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

<!--suppress SpellCheckingInspection -->
<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ContentCard from '@/components/Cards/ContentCard';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import LevelSelector from '@/components/Scaffolding/Logging/Channels/Common/LevelSelector';

export default {
  name: 'ErrorLogChannel',
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
      path: 'logging/channels/errorlog/level',
    });
    this.level = data.value || 'debug';

    this.loading = false;
  },
  methods: {
    persistLevel(level) {
      const name = 'Errorlog Logging Level';
      const path = 'logging/channels/errorlog/level';

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
