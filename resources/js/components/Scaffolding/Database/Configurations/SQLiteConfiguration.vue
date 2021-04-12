<template>
  <div>
    <row v-if="fetchingMutations">
      <column centered offset="4" size="4">
        <p>Restoring Configuration...</p>
        <indeterminate-progress-bar />
      </column>
    </row>

    <row v-else>
      <column offset="1" size="10">
        <basic-content-section heading="Basic">
          <row>
            <column v-for="input in inputs" :key="input.name" size="4">
              <pg-labeled-input
                :ref="input.ref"
                :class="input.getClassBindings ? input.getClassBindings() : {}"
                :disabled="input.isDisabled ? input.isDisabled() : false"
                :input.sync="$data[input.sync]"
                :label="input.name"
                :placeholder="input.pHolder"
                :value="$data[input.sync]"
                @input="persistConfig"
              />
            </column>
          </row>
        </basic-content-section>

        <basic-content-section heading="Advanced" prepend-separator>
          <row>
            <column centered>
              <toggle-button
                :state.sync="inMemory"
                :value="inMemory"
                :hide-icon="false"
                off-color-class="danger"
                small
                text="In-Memory Database"
                @input="persistConfig"
              />
              <toggle-button
                :state.sync="fkConstraints"
                :value="fkConstraints"
                :hide-icon="false"
                off-color-class="danger"
                small
                text="Foreign Key Constraints"
                @input="persistConfig"
              />
            </column>
          </row>
        </basic-content-section>
      </column>
    </row>
  </div>
</template>

<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import ToggleButton from '@/components/Forms/Buttons/ToggleButton';
import BasicContentSection from '@/components/Content/BasicContentSection';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'SQLiteConfiguration',
  components: {
    IndeterminateProgressBar,
    BasicContentSection,
    ToggleButton,
    PgLabeledInput,
    Column,
    Row,
  },
  mixins: [asyncImports, mutations],
  props: {
    heading: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      path: '',
      name: 'database.sqlite',
      prefix: '',
      inMemory: false,
      fkConstraints: true,

      mounted: false,

      inputs: [
        {
          name: 'Database Path',
          sync: 'path',
          pHolder: '**Project Root**/database',
          ref: 'dbPathInput',
          isDisabled: () => this.inMemory,
          getClassBindings: () => ({
            'disabled-overlay': this.inMemory,
          }),
        },
        {
          name: 'Database Name',
          sync: 'name',
          pHolder: 'database.sqlite',
          ref: 'dbNameInput',
        },
        {
          name: 'Table Prefix',
          sync: 'prefix',
          pHolder: '',
        },
      ],
    };
  },
  watch: {
    inMemory: {
      handler(v) {
        if (v && this.$refs.dbNameInput) {
          setTimeout(() => {
            this.$nextTick(() => {
              this.$refs.dbNameInput[0].focus();
            });
          }, 50);
        }
      },
    },
  },
  async created() {
    this.registerMutable(
      'SQLite Configuration',
      'database/configurations/sqlite',
      {
        then: (value) => {
          const val = value || {};

          this.path = val.path || this.path;
          this.name = val.name || this.name;
          this.prefix = val.prefix || this.prefix;
          this.inMemory = val.inMemory !== undefined ? val.inMemory : this.inMemory;
          this.fkConstraints = val.fkConstraints !== undefined ? val.fkConstraints : this.fkConstraints;
        },
      },
    );
  },
  methods: {
    persistConfig() {
      this.$nextTick(() => {
        const config = {
          path: this.path,
          name: this.name,
          prefix: this.prefix,
          inMemory: this.inMemory,
          fkConstraints: this.fkConstraints,
        };

        const payload = {
          name: 'SQLite Configuration',
          path: 'database/configurations/sqlite',
          value: config,
        };

        this.mutate(payload);
      });
    },
  },
};
</script>

<style scoped></style>
