<template>
  <div v-if="loading || fetchingMutations">
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
      >
        <pg-labeled-input
          v-model="path"
          label="Path"
          :tooltip-delay="250"
          tooltip-placement="left"
          :tooltip-distance="30"
          placeholder="The path where the logs will be stored..."
          :tooltip="`Laravel path helpers will automatically be used if the path is prefixed
                    with a corresponding directory.
                    e.g. If the path is
                    <span class='text-complete bold'>storage/logs/laravel.log</span>,
                    then the computed path is
                    <span class='text-complete bold'>storage_path('logs/laravel.log')</span>`
          "
          @input="persistPath"
        />
      </column>

      <column>
        <separator />
      </column>

      <column
        size="6"
        offset="3"
        centered
      >
        <p class="text-primary">
          Logging Level
        </p>
        <level-selector
          :default="lvl"
          @change="persistLevel"
        />
      </column>
    </row>
  </div>
</template>

<script>
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import LevelSelector from '@/components/Scaffolding/Logging/Channels/Common/LevelSelector';

export default {
  name: 'CommonConfiguration',
  components: {
    IndeterminateProgressBar,
    LevelSelector,
    Separator,
    PgLabeledInput,
    Column,
    Row,
  },
  mixins: [mutations],
  props: {
    inputMutationPath: {
      type: String,
      default: null,
    },
    defaultPath: {
      type: String,
      default: null,
    },
    level: {
      type: String,
      default: null,
    },
    levelPath: {
      type: String,
      default: null,
    },
    channelName: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      loading: false,
      path: this.defaultPath || '',
      lvl: this.level || 'info',
    };
  },
  async created() {
    this.loading = true;
    await this.syncPath();
    await this.syncLevel();
    this.loading = false;
    this.$emit('loaded');
  },
  methods: {
    async syncPath() {
      const { data } = await this.mutation({ path: this.inputMutationPath });
      this.path = data.value || this.defaultPath;
    },

    async syncLevel() {
      const { data } = await this.mutation({ path: this.levelPath });
      this.lvl = data.value || this.lvl;
    },

    persistPath() {
      const payload = {
        name: 'Path',
        path: this.inputMutationPath,
        value: this.path,
      };

      this.mutate(payload);
    },

    persistLevel(level) {
      const name = `${this.channelName} Channel Level`;

      const payload = {
        name,
        path: this.levelPath,
        value: level,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped></style>
