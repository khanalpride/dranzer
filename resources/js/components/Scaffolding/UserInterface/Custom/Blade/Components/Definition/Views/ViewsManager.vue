<template>
    <div>
      <row v-if="loading">
        <column size="4" offset="4">
          <indeterminate-progress-bar />
        </column>
      </row>
      <row v-else>
        <column>
          <pg-input v-model="definition" placeholder="Comma separated view names..."
                    @keyup.native.enter="onCreateViewFromInput"/>
        </column>

        <column push10 v-if="views.length">
          <row :key="view.id" v-for="(view, index) in views">
            <column :push15="index > 0">
              <blade-view :layout="layout"
                          :persisted-view="view"
                          :theme-files="themeFiles"
                          :index-file="indexFile"
                          :controllers="controllers"
                          @updated="onViewUpdated"
                          @delete="onDeleteView(view)"/>
            </column>
          </row>
        </column>
      </row>
    </div>
</template>

<script>
import mutations from '@/mixins/mutations';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import BladeView from '@/components/Scaffolding/UserInterface/Custom/Blade/Components/Definition/Views/BladeView';
import PgInput from '@/components/Forms/PgInput';
import sharedMutations from '@/mixins/shared_mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'ViewsManager',
  props: {
    layout: Object,
    themeFiles: Array,
    indexFile: {},
  },
  mixins: [mutations, sharedMutations],
  components: {
    IndeterminateProgressBar,
    PgInput,
    BladeView,
    Column,
    Row,
  },
  data() {
    return {
      loading: false,

      definition: '',

      controllers: [],

      views: [],

      deleting: [],
    };
  },
  async created() {
    this.loading = true;
    await this.assignControllers();
    await this.syncViews();
    this.loading = false;
  },
  methods: {
    async syncViews() {
      const { data } = await this.mutation({ path: `ui/views/${this.layout.id}/`, like: true, refresh: true });
      this.views = data.value ? data.value.map((v) => v.value) : [];
      this.$emit('view-count-changed', this.views.length);
    },

    onCreateViewFromInput() {
      const input = this.definition.trim();

      if (!input || input.trim() === '') {
        this.definition = '';
        return;
      }

      const views = input.split(',');

      views.forEach((view) => {
        const viewName = view.replace(/\s/g, '').trim();

        if (!/^[a-zA-Z0-9_-]+$/g.test(viewName)) {
          return false;
        }

        if (
          !this.views.find(
            (v) => v.name.toLowerCase().trim()
              === viewName.toLowerCase().trim(),
          )
        ) {
          const controller = this.controllers.find((c) => c.name.toLowerCase().indexOf(viewName.toLowerCase()) > -1) || {};
          const newView = {
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            name: viewName,
            uri: `/${viewName}`,
            controller: controller.id || null,
            layoutId: this.layout.id,
            layoutFile: null,
            customContentWrapper: false,
            contentWrapper: '',
          };

          this.views.push(newView);

          this.persistView(newView);
        }

        return true;
      });

      this.$emit('view-count-changed', this.views.length);

      this.definition = '';
    },

    persistView(view) {
      const payload = {
        name: 'Blade View',
        path: `ui/views/${this.layout.id}/${view.id}`,
        value: view,
      };

      this.mutate(payload);
    },

    onViewUpdated(view) {
      const viewIndex = this.views.findIndex((v) => v.id === view.id);
      if (viewIndex > -1) {
        this.views[viewIndex].layoutFile = view.layoutFile;
        this.views[viewIndex].controller = view.controller;
        this.persistView(view);
      }
    },

    async onDeleteView(view) {
      const viewIndex = this.views.findIndex((v) => v.id === view.id);
      if (viewIndex > -1) {
        this.deleting.push(view.id);
        const { status } = await this.deleteMutation(`ui/views/${this.layout.id}/${view.id}`);
        this.deleting.splice(this.deleting.indexOf(view.id), 1);
        if (status === 201 || status === 404) {
          this.views.splice(viewIndex, 1);
          this.$emit('view-count-changed', this.views.length);
        }
      }
    },
  },
};
</script>

<style scoped>

</style>
