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
            <column
              v-for="input in inputs"
              :key="input.name"
              :offset="input.offset || ''"
              :size="input.width || 4"
            >
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
            <column
              v-for="input in advancedInputs"
              :key="input.name"
              :offset="input.offset || ''"
              :size="input.width || 4"
            >
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
          <row push10>
            <column>
              <row>
                <column offset="3" size="6">
                  <p class="text-center">Charset</p>
                  <el-select
                    v-model="charset"
                    class="el-sel-full-width"
                    :disabled="project && project.downloaded"
                    filterable
                    @change="persistConfig"
                  >
                    <el-option
                      v-for="c in charsets"
                      :key="c"
                      :label="c"
                      :value="c"
                    />
                  </el-select>
                </column>
              </row>
            </column>
          </row>
          <row push15>
            <column centered>
              <toggle-button
                small
                :value="prefixIndexes"
                :hide-icon="false"
                :state.sync="prefixIndexes"
                off-color-class="danger"
                state-tooltip-suffix="Index Prefixes"
                text="Prefix Indexes"
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
import { mapState } from 'vuex';

import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import charsets from '@/data/scaffolding/database/postgres/charsets';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import ToggleButton from '@/components/Forms/Buttons/ToggleButton';
import BasicContentSection from '@/components/Content/BasicContentSection';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'PostgreSQLConfiguration',
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
      url: '',
      host: '127.0.0.1',
      port: 5432,
      database: 'forge',
      username: 'root',
      password: '',
      prefix: '',
      prefixIndexes: true,

      charset: 'utf8',
      charsets: [],

      schema: 'public',
      sslMode: 'prefer',

      mounted: false,

      inputs: [
        {
          name: 'Database URL',
          sync: 'url',
          pHolder: '',
          ref: 'dbPathInput',
          isDisabled: () => this.inMemory,
          getClassBindings: () => ({
            'disabled-overlay': this.inMemory,
          }),
        },
        {
          name: 'Database Host',
          sync: 'host',
          pHolder: '127.0.0.1',
          ref: 'dbHostInput',
        },
        {
          name: 'Database Port',
          sync: 'port',
          pHolder: '5432',
        },
        {
          name: 'Database Name',
          sync: 'database',
          pHolder: 'forge',
        },
        {
          name: 'Database Username',
          sync: 'username',
          pHolder: 'forge',
        },
        {
          name: 'Table Prefix',
          sync: 'prefix',
          pHolder: '',
        },
      ],

      advancedInputs: [
        {
          name: 'Database Schema',
          sync: 'schema',
          pHolder: 'public',
          width: 3,
          offset: 3,
        },
        {
          name: 'SSL Mode',
          sync: 'sslMode',
          pHolder: 'prefer',
          width: 3,
        },
      ],
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  created() {
    this.charsets = charsets.find((c) => c.version === 'latest').charsets;

    this.database = this.project.name || this.database;

    this.registerMutable(
      'PostgreSQL Configuration',
      'database/configurations/postgresql',
      {
        then: (value) => {
          const val = value || {};

          this.url = val.url || this.url;
          this.host = val.host || this.host;
          this.port = val.port || this.port;
          this.database = val.database || this.database;
          this.username = val.username || this.username;
          this.password = val.password || this.password;
          this.prefix = val.prefix || this.prefix;
          this.prefixIndexes = val.prefixIndexes !== undefined ? val.prefixIndexes : this.prefixIndexes;
          this.charset = val.charset || this.charset;
          this.schema = val.schema || this.schema;
          this.sslMode = val.sslMode || this.sslMode;
        },
      },
    );
  },

  methods: {
    persistConfig() {
      this.$nextTick(() => {
        const config = {
          url: this.url,
          host: this.host,
          port: this.port,
          database: this.database,
          username: this.username,
          password: this.password,
          prefix: this.prefix,
          prefixIndexes: this.prefixIndexes,
          charset: this.charset,
          schema: this.schema,
          sslMode: this.sslMode,
        };

        const payload = {
          name: 'PostgreSQL Configuration',
          path: 'database/configurations/postgresql',
          value: config,
        };

        this.mutate(payload);
      });
    },
  },
};
</script>

<style scoped></style>
