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
              :offset="input.offset || ''"
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

          <row :push10="optionsInputs.length">
            <column
              v-for="input in optionsInputs"
              :key="input.name"
              :size="input.width || 4"
              :offset="input.offset || ''"
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
          <row push10>
            <column centered>
              <toggle-button
                small
                :hide-icon="false"
                :state.sync="multipleHosts"
                :value="multipleHosts"
                text="Multiple Hosts"
                off-color-class="danger"
                @input="persistConfig"
              />
            </column>
          </row>

          <row v-if="multipleHosts" push10>
            <column>
              <row>
                <column size="6" offset="3">
                  <pg-labeled-input
                    v-model="replicaSet"
                    label="Replica Set"
                    @input="persistConfig"
                  />
                </column>
              </row>
            </column>

            <column>
              <row v-if="hosts.length">
                <column
                  v-for="host in hosts"
                  :key="host.id"
                  size="6"
                  offset="3"
                >
                  <form-input-group compact>
                    <pg-input
                      :ref="host.id"
                      v-model="host.name"
                      placeholder="Host Name"
                      @input="persistConfig"
                    />
                    <button
                      class="btn btn-danger btn-sm"
                      @click="deleteHost(host)"
                    >
                      <i class="fa fa-close" />
                    </button>
                  </form-input-group>
                </column>
              </row>

              <row :push10="hosts.length > 0" :push5="hosts.length === 0">
                <column centered>
                  <button class="btn btn-primary" @click="addHost">
                    <i class="fa fa-plus" /> Add Host
                  </button>
                </column>
              </row>
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

import Row from '@/components/Layout/Grid/Row';
import PgInput from '@/components/Forms/PgInput';
import Column from '@/components/Layout/Grid/Column';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import ToggleButton from '@/components/Forms/Buttons/ToggleButton';
import BasicContentSection from '@/components/Content/BasicContentSection';

import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'MongoDBConfiguration',
  components: {
    FormInputGroup,
    PgInput,
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
      port: 27017,
      database: 'forge',
      username: 'root',
      password: '',
      dbAuthDatabase: 'admin',

      multipleHosts: false,

      replicaSet: '',
      hosts: [],

      mounted: false,

      inputs: [
        {
          name: 'Database Host',
          sync: 'host',
          pHolder: '127.0.0.1',
          ref: 'dbHostInput',
          width: 6,
        },
        {
          name: 'Database Port',
          sync: 'port',
          pHolder: '27017',
          width: 6,
        },
        {
          name: 'Database Name',
          sync: 'database',
          pHolder: 'forge',
          width: 6,
        },
        {
          name: 'Database Username',
          sync: 'username',
          pHolder: 'forge',
          width: 6,
        },
      ],

      optionsInputs: [
        {
          name: 'Authentication Database',
          sync: 'dbAuthDatabase',
          pHolder: 'admin',
          width: 6,
          offset: 3,
        },
      ],
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  created() {
    this.database = this.project.name || this.database;

    this.registerMutable(
      'MongoDB Configuration',
      'database/configurations/mongodb',
      {
        then: (value) => {
          const val = value || {};

          this.url = val.url || this.url;
          this.host = val.host || this.host;
          this.port = val.port || this.port;
          this.database = val.database || this.database;
          this.username = val.username || this.username;
          this.dbAuthDatabase = val.dbAuthDatabase || this.dbAuthDatabase;
          this.multipleHosts = val.multipleHosts !== undefined ? val.multipleHosts : this.multipleHosts;
          this.defaultFocusableInputRef = val.defaultFocusableInputRef || this.defaultFocusableInputRef;
          this.replicaSet = val.replicaSet || this.replicaSet;
          this.hosts = val.hosts || this.hosts;
        },
      },
    );
  },

  methods: {
    addHost() {
      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);

      this.hosts.push({
        id,
        name: '',
      });

      this.$nextTick(() => {
        if (this.$refs[id]) {
          this.$refs[id][0].focus();
        }
      });
    },

    deleteHost(host) {
      const index = this.hosts.findIndex((h) => h.id === host.id);

      if (index > -1) {
        this.hosts.splice(index, 1);
        this.persistConfig();
      }
    },

    persistConfig() {
      this.$nextTick(() => {
        const config = {
          url: this.url,
          host: this.host,
          port: this.port,
          database: this.database,
          username: this.username,
          dbAuthDatabase: this.dbAuthDatabase,
          multipleHosts: this.multipleHosts,
          defaultFocusableInputRef: this.defaultFocusableInputRef,
          replicaSet: this.replicaSet,
          hosts: this.hosts.filter((h) => h.name.trim() !== ''),
        };

        const payload = {
          name: 'MongoDB Configuration',
          path: 'database/configurations/mongodb',
          value: config,
        };

        this.mutate(payload);
      });
    },
  },
};
</script>

<style scoped></style>
