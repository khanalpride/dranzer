<template>
  <row>
    <column>
      <pg-check-box color-class="green"
                    :disabled="deleting"
                    :value="1"
                    :label="label"
                    @change="onControllerStateChanged" />
    </column>

    <column size="10" class="m-l-30">
      <row>
        <column>
          <pg-check-box v-model="entity.createRouteGroup"
                        :disabled="deleting"
                        no-margin
                        label="Create Routes Group" />
        </column>
        <column size="6">
          <pg-check-box v-model="entity.isSAC" :disabled="deleting" no-margin label="Single Action Controller" @change="onSACToggled" />
          <pg-check-box v-model="entity.isRC" :disabled="deleting" no-margin label="Resource Controller" @change="onResourceModeToggled" />
        </column>
      </row>
    </column>

    <column size="11" class="m-l-30" v-if="entity.isRC">
      <row>
        <column centered>
          <basic-content-section heading="Resource Methods" prepend-separator>
            <pg-check-box no-margin
                          centered
                          color-class="primary"
                          :disabled="deleting"
                          :value="entity.selectedMethods.includes(method.name)"
                          :label="method.name"
                          :key="method.id"
                          v-for="method in entity.reservedMethods"
                          @change="onReservedMethodToggled($event, method)" />
          </basic-content-section>
        </column>
        <column>
          <basic-content-section heading="Model Binding" prepend-separator>
            <row>
              <column size="4" offset="4">
                <el-select filterable
                           clearable
                           placeholder="Select model to bind..."
                           class="el-sel-full-width"
                           v-model="entity.resourceModel">
                  <el-option :key="model.id"
                             :value="model.id"
                             :label="model.modelName"
                             v-for="model in models" />
                </el-select>
              </column>
            </row>
          </basic-content-section>
        </column>
      </row>
    </column>

    <column :push15="!entity.isRC" size="11" class="m-l-30" v-if="methodTabs.length">
      <basic-content-section heading="Methods" prepend-separator>
        <tabs-manager ref="methodsTabManager"
                      :tabs="methodTabs"
                      :path="`config/controllers/tabs/stmts/method/${entity.id}`"
                      @remove="onRemoveMethodTab">
          <template :slot="tab.id" v-for="tab in methodTabs">
            <basic-content-section :key="tab.id"
                                   :heading="`${tab.method} Statements (${getMethodStatements(tab.method).length})`">
            <row>
                <column :key="stmt.id" v-for="stmt in getMethodStatements(tab.method)">
                  <pg-check-box no-margin :value="1" disabled :label="stmt.humanReadable || 'Controller Stmt'" />
                </column>
                <column>
                  <basic-content-section heading="Presets" :prepend-separator="getMethodStatements(tab.method).length > 0">
                    <tabs-manager :key="tab.id" :tabs="statementTabs" :path="`config/controllers/tabs/stmts/${entity.id}`">
                      <template slot="models">
                        <controller-model-integration :key="tab.id"
                                                      :blueprints="blueprints"
                                                      :controller-id="entity.id"
                                                      :method="tab.method"
                                                      :eloquent-relations="eloquentRelations"
                                                      :persisted="getPersistedModelStmts(tab)"
                                                      @stmts-changed="onModelStatementsChanged($event, tab)" />
                      </template>
                    </tabs-manager>
                  </basic-content-section>
                </column>
              </row>
            </basic-content-section>
          </template>
        </tabs-manager>
      </basic-content-section>
    </column>
  </row>
</template>

<script>
import entity from '@/mixins/entity';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import ControllerModelIntegration from '@/components/Scaffolding/Controllers/Integrations/ControllerModelIntegration';
import TabsManager from '@/components/Tabs/TabsManager';
import BasicContentSection from '@/components/Content/BasicContentSection';

export default {
  name: 'Controller',
  mixins: [entity],
  components: {
    BasicContentSection,
    TabsManager,
    ControllerModelIntegration,
    PgCheckBox,
    Column,
    Row,
  },
  props: {
    persisted: {},
    defaultPath: String,
    type: String,
    deleting: Boolean,
    blueprints: Array,
    views: Array,
    eloquentRelations: Array,
  },
  data() {
    return {
      ready: false,

      methodsDefinition: '',

      entity: {
        index: 0,
        name: '',
        path: this.defaultPath,
        createRouteGroup: true,
        isRC: false,
        isSAC: true,
        resourceModel: null,
        methods: [],
        reservedMethods: [
          'index',
          'show',
          'edit',
          'create',
          'store',
          'update',
          'destroy',
        ],
        selectedMethods: [],
        customMethods: [],
        stmts: {},
        preset: false,
        visible: true,
      },

      validation: {
        patterns: {
          name: {
            resource: /^[a-z]{3,}$/,
            controller: /^[A-Z]([a-zA-Z]+)?Controller$/,
          },
        },
      },
    };
  },
  computed: {
    hasStatements() {
      return this.stmts.length;
    },

    label() {
      return `${this.entity.name} <span class="text-complete m-l-5"><i class="fa fa-folder"></i> ${this.entity.path.replace(/\//g, '\\')}</span>`;
    },

    models() {
      return this.blueprints.filter((s) => s.modelName && s.modelName.trim() !== '' && s.visible !== false);
    },

    persistable() {
      if (!this.entity || !this.entity.id) {
        return {};
      }

      return {
        id: this.entity.id,
        name: this.entity.name,
        path: this.entity.path,
        createRouteGroup: this.entity.createRouteGroup,
        isRC: this.entity.isRC,
        isSAC: this.entity.isSAC,
        resourceModel: this.entity.resourceModel,
        methods: this.entity.methods,
        selectedMethods: this.entity.selectedMethods,
        type: this.type,
        stmts: this.entity.stmts,
        customMethods: this.entity.customMethods,
        visible: this.entity.visible,
        preset: this.entity.preset,
      };
    },

    methodTabs() {
      return this.views.filter((v) => v.controller === this.entity.id).map((v) => ({
        id: `V${v.id}`,
        label: `show${this.str.studly(v.name)}`,
        method: `show${this.str.studly(v.name)}`,
        removable: false,
      }));
    },

    statementTabs() {
      return [
        {
          id: 'models',
          label: 'Eloquent Presets',
        },
      ];
    },
  },
  watch: {
    persistable: {
      handler() {
        if (!this.ready) {
          return;
        }

        this.broadcastUpdate();
      },
      deep: true,
    },
  },
  created() {
    this.entity.reservedMethods = this.entity.reservedMethods.map((method) => ({
      id: method,
      name: method,
    }));

    this.$nextTick(() => {
      this.focusNameInput();
    });

    this.$nextTick(() => {
      this.ready = true;
    });
  },
  methods: {
    focusNameInput() {
      this.$nextTick(() => {
        if (this.$refs.nameInput) {
          this.$refs.nameInput.focus();
        }
      });
    },

    getPersistedModelStmts(modelTab) {
      if (this.entity.stmts[modelTab.method]) {
        return this.entity.stmts[modelTab.method].modelStmts || [];
      }

      return [];
    },

    getMethodStatements(method) {
      const stmts = [];

      if (this.entity.stmts[method]) {
        const { modelStmts } = this.entity.stmts[method];
        if (modelStmts) {
          stmts.push(...modelStmts.stmts);
        }
      }

      return stmts;
    },

    /**
     *
     *
     */
    broadcastUpdate() {
      this.$emit('update', this.persistable);
    },

    /**
     *
     * @param methods
     */
    onMethodSelectionChanged(methods) {
      const customMethods = methods.filter(
        (method) => !this.entity.reservedMethods.find((m) => m.name === method),
      );

      this.entity.methods = customMethods.map((method) => ({
        id: method,
        name: method,
      }));
    },

    onReservedMethodToggled(active, method) {
      if (active && !this.entity.selectedMethods.includes(method.name)) {
        this.entity.selectedMethods.push(method.name);
      }

      if (!active) {
        this.entity.selectedMethods = this.entity.selectedMethods.filter((m) => m !== method.name);
      }
    },

    /**
     *
     * @param active
     */
    onResourceModeToggled(active) {
      if (active) {
        this.entity.isSAC = false;

        const resourceMethods = ['store', 'update', 'destroy', 'index', 'create', 'edit', 'show'];

        resourceMethods.forEach((method) => {
          if (!this.entity.selectedMethods.includes(method)) {
            this.entity.selectedMethods.push(method);
          }
        });

        if (!this.entity.name || this.entity.name.trim() === '') {
          this.focusNameInput();
        }
      }
    },

    /**
     *
     * @param active
     */
    onSACToggled(active) {
      if (active) {
        this.entity.isRC = false;
      }
    },

    onControllerStateChanged(active) {
      if (!active) {
        this.$emit('delete');
      }
    },

    onModelStatementsChanged(updatedStmts, methodTab) {
      this.entity.stmts = {
        ...this.entity.stmts,
        [methodTab.method]: {
          modelStmts: updatedStmts,
        },
      };
    },

    onRemoveMethodTab(customMethodId) {
      const methodIndex = this.entity.customMethods.findIndex((c) => c.id === customMethodId);

      if (methodIndex > -1) {
        this.entity.customMethods.splice(methodIndex, 1);
        if (this.$refs.methodsTabManager) {
          this.$refs.methodsTabManager.activateTabByIndex(this.methodTabs.length - 1);
        }
      }
    },
  },
};
</script>

<style scoped></style>
