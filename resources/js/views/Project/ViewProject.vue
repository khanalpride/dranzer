<template>
  <div>
    <stick-up-modal ref="deleteConfirmationModal" dismissible>
      <template slot="header">
        <row push10>
          <column centered>
            <div v-if="!deleting">
              <h6 class="text-primary hint-text bold">
                Confirmation
              </h6>
              <hr>
              <p class="text-info hint-text bold">
                Are you sure you want to delete this project?
              </p>
              <hr>
            </div>
            <p v-else class="text-primary">
              <i class="fa fa-trash"/> Deleting Project...
            </p>
          </column>
        </row>
      </template>

      <row centered push10>
        <column v-if="deleting">
          <indeterminate-circular-progress centered color="primary" />
        </column>

        <column
          v-else
          centered
          push5
        >
          <button
            class="btn btn-danger"
            @click="deleteProject"
          >
            <i class="fa fa-check" /> Yes, Delete Project
          </button>
          <button
            class="btn btn-info m-l-10"
            @click="hideDeleteConfirmationModal"
          >
            <i class="fa fa-window-restore" /> No, Cancel Deletion
          </button>
        </column>
      </row>
    </stick-up-modal>

    <div>
      <initializing-progress-container initializer-text="Restoring Project" :initializing="loading" />
    </div>

    <div v-if="!loading">
      <div v-if="!validated">
        <row>
          <column centered offset="2" size="8">
            <p class="text-center text-danger">
              <i class="fa fa-exclamation-triangle" /> Sorry,
              but the requested project does not exist!
            </p>
            <router-link class="btn btn-primary btn-sm m-t-10" to="/">
              <i class="fa fa-plus" /> Create New Project
            </router-link>
            <router-link
              v-if="projects.length"
              class="btn btn-complete btn-sm m-t-10 m-l-5"
              to="/projects">
              <i class="fa fa-list-ul" />
              Browse Projects ({{ projects.length }})
            </router-link>
          </column>
        </row>
      </div>

      <div v-else>
        <scaffolding-project
          v-if="type === 'scaffolding'"
          @delete="showDeleteConfirmationModal"
          @initialized="initializing = false"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapMutations, mapState } from 'vuex';

import IndeterminateCircularProgress from '@/components/Progress/IndeterminateCircularProgress';
import StickUpModal from '@/components/Modals/StickUpModal';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';

import ScaffoldingProject from '@/views/Project/Types/ScaffoldingProject';
import InitializingProgressContainer from '@/components/Scaffolding/Containers/Progress/InitializingProgressContainer';
import project from '../../mixins/project';
import mutations from '../../mixins/mutations';
import asyncImports from '../../mixins/async_imports';

const { axios } = window;

export default {
  name: 'ViewProject',
  components: {
    InitializingProgressContainer,
    ScaffoldingProject,
    Column,
    Row,
    StickUpModal,
    IndeterminateCircularProgress,
  },
  mixins: [asyncImports, mutations, project],
  async beforeRouteUpdate(to, _, next) {
    await this.initProject(to.params.uuid);
    next();
  },
  data() {
    return {
      loading: false,

      validated: false,

      type: null,

      deleting: false,

      deletionError: null,

      initializing: false,
    };
  },
  computed: {
    ...mapState('app', ['projects']),
    ...mapGetters('project', ['projectId']),
  },
  async created() {
    await this.initProject(this.$route.params.uuid);
  },
  methods: {
    ...mapMutations('app', ['REMOVE_PROJECT']),
    ...mapMutations('project', ['SET_PROJECT']),

    async initProject(uuid) {
      if (!uuid) {
        this.project = null;
        this.validated = false;
        this.loading = false;
        return;
      }

      this.SET_PROJECT(null);

      this.title('Loading Project...', false);

      this.loading = true;
      const { data } = await axios.post(`/projects/${uuid}`);
      this.loading = false;

      const valid = data.project && data.project.uuid;

      this.validated = valid;

      if (!valid) {
        this.title();
        return;
      }

      this.initializing = true;

      this.type = data.project.type;

      this.title(data.project.name);

      this.SET_PROJECT(data.project);
    },

    async deleteProject() {
      this.deleting = true;

      const { status, data } = await axios.delete(
        `/projects/${this.projectId}`,
      );

      this.deleting = false;

      if (status === 204) {
        this.hideDeleteConfirmationModal();
        this.REMOVE_PROJECT(this.project);
        this.SET_PROJECT(null);
        setTimeout(async () => {
          await this.$router.push('/');
        }, 250);
      } else {
        this.deletionError = data.message;
      }
    },

    showDeleteConfirmationModal() {
      this.$refs.deleteConfirmationModal.show();
    },

    hideDeleteConfirmationModal() {
      this.$refs.deleteConfirmationModal.hide();
      this.deletionError = null;
    },

    focusSearchInput() {
      this.$refs.searchContainer.focus();
    },
  },
};
</script>

<style scoped></style>
