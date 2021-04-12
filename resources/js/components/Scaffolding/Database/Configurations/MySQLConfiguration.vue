<template>
  <div>
    <row v-if="fetchingMutations">
      <column size="4" offset="4" centered>
        <p>Restoring Configuration...</p>
        <indeterminate-progress-bar />
      </column>
    </row>

    <row v-else>
      <column size="10" offset="1">
        <basic-content-section heading="Basic">
          <row>
            <column
              v-for="input in inputs"
              :key="input.name"
              :size="input.width || 4"
            >
              <pg-labeled-input
                :ref="input.ref"
                :label="input.name"
                :value="$data[input.sync]"
                :input.sync="$data[input.sync]"
                :placeholder="input.pHolder"
                :disabled="input.isDisabled ? input.isDisabled() : false"
                :class="input.getClassBindings ? input.getClassBindings() : {}"
                @input="persistConfig"
              />
            </column>
          </row>
        </basic-content-section>

        <basic-content-section heading="Advanced" prepend-separator>
          <row>
            <column>
              <row>
                <column size="4" offset="2">
                  <p class="text-center">Charset</p>
                  <el-select
                    v-model="charset"
                    filterable
                    class="el-sel-full-width"
                    :disabled="project && project.downloaded"
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
                <column size="4">
                  <p class="text-center">Collation</p>
                  <el-select
                    v-model="collation"
                    filterable
                    class="el-sel-full-width"
                    :disabled="project && project.downloaded"
                    @change="persistConfig"
                  >
                    <el-option
                      v-for="c in collations"
                      :key="c"
                      :label="c"
                      :value="c"
                    />
                  </el-select>
                </column>
              </row>
            </column>
          </row>
          <row push10>
            <column size="8" offset="2">
              <separator />
            </column>
            <column centered>
              <toggle-button
                small
                :hide-icon="false"
                :value="prefixIndexes"
                :state.sync="prefixIndexes"
                text="Prefix Indexes"
                off-color-class="danger"
                state-tooltip-suffix="Index Prefixes"
                @input="persistConfig"
              />
              <toggle-button
                small
                :hide-icon="false"
                :value="strict"
                :state.sync="strict"
                text="Strict Mode"
                off-color-class="danger"
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

import charsets from '@/data/scaffolding/database/mysql/charsets';
import collations from '@/data/scaffolding/database/mysql/collations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import ToggleButton from '@/components/Forms/Buttons/ToggleButton';
import BasicContentSection from '@/components/Content/BasicContentSection';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'MySQLConfiguration',
  components: {
    Separator,
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
      port: 3306,
      database: 'forge',
      username: 'root',
      password: '',
      prefix: '',
      prefixIndexes: true,
      strict: true,
      engine: '',

      charset: 'utf8mb4',
      charsets: [],

      collation: 'utf8mb4_unicode_ci',
      collations: [],

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
          pHolder: '3306',
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
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  created() {
    this.charsets = charsets.find((c) => c.version === 8).charsets;
    this.collations = collations.find((c) => c.version === 8).collations;

    this.database = this.project.name || this.database;

    this.registerMutable(
      'MySQL Configuration',
      'database/configurations/mysql',
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
          this.strict = val.strict !== undefined ? val.strict : this.strict;
          this.engine = val.engine || this.engine;
          this.charset = val.charset || this.charset;
          this.collation = val.collation || this.collation;
        },
      },
    );
  },

  methods: {
    charsetChanged() {
      this.persistConfig();
    },

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
          strict: this.strict,
          engine: this.engine,
          charset: this.charset,
          collation: this.collation,
        };

        const payload = {
          name: 'MySQL Configuration',
          path: 'database/configurations/mysql',
          value: config,
        };

        this.mutate(payload);
      });
    },
  },
};
</script>

<style scoped></style>
