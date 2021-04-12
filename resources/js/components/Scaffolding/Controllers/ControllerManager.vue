<template>
  <scaffolding-component-container :heading="moduleHeading" :loading="loading || fetchingMutations">
    <row>
      <column size="8">
        <pg-input v-model="definition"
                  placeholder="Enter comma separated controller names..."
                  @keydown.enter.native="onCreateControllersFromInput" />
      </column>
      <column size="4">
        <pg-input v-model="query"
                  :disabled="controllers.length < 2"
                  placeholder="Search controllers..." />
      </column>
    </row>

    <row v-if="controllers.length">
      <column :push10="index > 0" :key="controller.id" v-for="(controller, index) in filteredControllers">
        <controller :key="controller.id"
                    :persisted="controller"
                    :deleting="isDeleting(controller)"
                    :default-path="getControllerDefaultPath(controller)"
                    :type="type"
                    :blueprints="blueprints"
                    :views="views"
                    :eloquent-relations="eloquentRelations"
                    @add="addController"
                    @update="onControllerUpdated($event, controller)"
                    @delete="onDeleteController(controller)"/>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import mutations from '@/mixins/mutations';
import Row from '@/components/Layout/Grid/Row';
import PgInput from '@/components/Forms/PgInput';
import asyncImports from '@/mixins/async_imports';
import Column from '@/components/Layout/Grid/Column';
import sharedMutations from '@/mixins/shared_mutations';
import Controller from '@/components/Scaffolding/Controllers/Controller';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';

export default {
  name: 'ControllerManager',
  props: {
    type: {
      type: String,
      required: true,
    },
    defaultPath: String,
    eloquentRelations: Array,
    showPresets: Boolean,
  },
  components: {
    PgInput,
    Column,
    Row,
    Controller,
    ScaffoldingComponentContainer,
  },
  mixins: [asyncImports, mutations, sharedMutations],
  data() {
    return {
      loading: false,

      definition: '',

      controllers: [],

      query: '',

      controllerPersistencePath: `controllers/${this.type}`,

      deleting: [],

      blueprints: [],

      views: [],
    };
  },
  computed: {
    moduleHeading() {
      const filteredControllersCount = this.filteredControllers.length;
      const { nonEmptyControllersCount } = this;

      if (filteredControllersCount === 0) {
        return 'Controllers';
      }

      if (filteredControllersCount === nonEmptyControllersCount) {
        return `Controllers (${filteredControllersCount})`;
      }

      return `Controllers (${filteredControllersCount} / ${nonEmptyControllersCount})`;
    },

    nonEmptyControllersCount() {
      return this.nonEmptyControllers.length;
    },

    nonEmptyControllers() {
      return this.controllers.filter((c) => c.name && c.name.trim() !== '');
    },

    filteredControllers() {
      return this.nonEmptyControllers.filter(
        (c) => c.visible
          && c.name.toLowerCase().replace('controller', '').indexOf(this.query.toLowerCase()) > -1,
      );
    },
  },
  async created() {
    this.loading = true;
    await this.assignBlueprints();
    await this.syncViews();
    await this.syncControllers();
    this.loading = false;
  },
  methods: {
    isDeleting(controller) {
      if (!controller || !controller.id) {
        return false;
      }

      return this.deleting.includes(controller.id);
    },

    async syncControllers() {
      const { data } = await this.mutation(
        { path: this.controllerPersistencePath, like: true, refresh: true },
      );

      this.controllers = (this.getPersistedMutationValue(data) || []);
    },

    async syncViews() {
      const { data } = await this.mutation({ path: 'ui/views/', like: true, refresh: true });
      this.views = data.value ? data.value.map((v) => v.value) : this.views;
    },

    getControllerDefaultPath(controller) {
      let defaultPath = this.type === 'web' ? 'app/Http/Controllers' : null;
      defaultPath = defaultPath || (this.type === 'api' ? 'app/Http/Controllers/API' : this.defaultPath);

      return controller.path || defaultPath;
    },

    hasController(controllerName) {
      return this.controllers.findIndex((c) => c.name === controllerName) > -1;
    },

    addController(data) {
      data = data || {};

      const { name } = data;

      if (!name) {
        return null;
      }

      let controller = this.controllers.find((c) => c.name.toLowerCase() === name.toLowerCase());

      if (controller) {
        return null;
      }

      controller = {
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        name,
        path: this.getControllerDefaultPath({}),
        preset: data.preset !== undefined ? data.preset : false,
        createRouteGroup: true,
        isRC: false,
        isSAC: true,
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
        stmts: [],
        visible: data.visible !== undefined ? data.visible : true,
      };

      this.controllers.push(controller);

      return controller;
    },

    persistController(update, controllerId) {
      this.mutate({
        name: `${this.str.humanize(this.type)} Controller`,
        path: `${this.controllerPersistencePath}/${controllerId}`,
        value: update,
      });
    },

    onControllerUpdated(update, controller) {
      if (this.isDeleting(controller)) {
        return;
      }

      const controllerIndex = this.controllers.findIndex((c) => c.id === controller.id);
      if (controllerIndex > -1) {
        this.controllers[controllerIndex].name = update.name;
        this.controllers[controllerIndex].stmts = update.stmts;
      }

      this.persistController(update, controller.id);
    },

    onDeleteController(controller) {
      const cIndex = this.controllers.findIndex((c) => c.id === controller.id);
      if (cIndex > -1) {
        this.deleting.push(controller.id);
        this.deleteMutation(`${this.controllerPersistencePath}/${controller.id}`, {
          then: () => {
            this.controllers.splice(cIndex, 1);
            this.deleting.splice(this.deleting.indexOf(controller.id), 1);
          },
        });
      }
    },

    onCreateControllersFromInput() {
      const names = this.definition.split(',');

      const persistable = [];

      names.forEach((name) => {
        if (!this.rgx.isNumericAlphaNumericOnly(name.trim())) {
          return false;
        }

        let controllerName = name.trim();

        controllerName = controllerName.toLowerCase().endsWith('controller') ? controllerName : `${controllerName}Controller`;

        controllerName = controllerName.substr(0, 1).toUpperCase() + controllerName.substr(1).trim();

        const addedController = this.addController({ name: controllerName, visible: true });

        if (addedController) {
          persistable.push(addedController);
        }

        return true;
      });

      const mutable = [];

      persistable.forEach((p) => {
        mutable.push({
          name: `${this.str.humanize(this.type)} Controller`,
          path: `${this.controllerPersistencePath}/${p.id}`,
          value: p,
        });
      });

      this.mutate({
        name: 'Controllers',
        bulk: true,
        value: mutable,
      });

      this.definition = '';
    },
  },
};
</script>

<style scoped></style>
