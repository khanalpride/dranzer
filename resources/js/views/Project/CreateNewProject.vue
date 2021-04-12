<template>
    <div>
        <div class="row">
            <div class="col-4 offset-4">
                <pg-labeled-input
                        ref="projectNameInput"
                        v-model="name"
                        :disabled="creating"
                        :processing="creating"
                        :validated="validated"
                        :validation-tooltip="validationTooltip"
                        label="New Project Name"
                        placeholder="Enter a name for your new project..."
                        processing-icon="fa fa-paper-plane"
                        processing-icon-color="primary"
                        required
                        empty-is-valid
                        show-processing-ellipses
                        validate
                        @input="handleProjectNameChange"
                        @keyup.13="createProject"
                />
            </div>

            <div class="col-4 offset-4">
                <div v-if="creating" class="row">
                    <div class="col-12">
                        <indeterminate-circular-progress color="primary"/>
                    </div>
                </div>

                <div v-if="error" class="row">
                    <div class="col-12">
                        <p class="text-danger bold">
                            <span v-html="error"/>
                        </p>
                        <separator v-if="projects.length"/>
                    </div>
                </div>
            </div>
        </div>

        <row push5>
            <column>
                <list-projects inline @deleted="focusProjectNameInput" />
            </column>
        </row>
    </div>
</template>

<!--suppress NpmUsedModulesInstalled -->
<script>
import { mapMutations, mapState } from 'vuex';

import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import IndeterminateCircularProgress from '@/components/Progress/IndeterminateCircularProgress';

import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import Row from '@/components/Layout/Grid/Row';
import ListProjects from '@/views/Project/ListProjects';

const { axios } = window;

export default {
  name: 'NewProject',
  components: {
    ListProjects,
    Row,
    Separator,
    Column,
    IndeterminateCircularProgress,
    PgLabeledInput,
  },
  data() {
    return {
      creating: false,
      loading: false,

      name: '',
      type: 'scaffolding',

      newestFirst: false,
      error: null,

      deleting: [],

      projectType: 'scaffolding',
    };
  },
  computed: {
    ...mapState('app', ['projects', 'loadingProjects', 'projectsOrder']),

    validated() {
      const name = this.name.trim();

      if (name === '') {
        return true;
      }

      return this.error
        ? false
        : name !== '' && name.length > 1 && name.length < 33;
    },

    validationTooltip() {
      if (this.validated) {
        return null;
      }

      const name = this.name.trim();

      if (name.length < 2) {
        return 'Project name must contain at-least 2 characters';
      }

      if (name.length > 32) {
        return 'Project name must not exceed 32 characters';
      }

      return 'Invalid project name';
    },
  },
  mounted() {
    this.focusProjectNameInput();
  },
  methods: {
    ...mapMutations('app', ['ADD_PROJECT']),

    focusProjectNameInput() {
      this.$nextTick(() => {
        this.$refs.projectNameInput.focus();
      });
    },

    handleProjectNameChange(name) {
      this.error = null;

      let buffer = '';

      for (let i = 0; i < name.length; i += 1) {
        const code = name.toString().charCodeAt(i);

        if (
          (code >= 65 && code <= 90)
          || (code >= 97 && code <= 122)
          || (code >= 48 && code <= 57)
          || code === 95
          || code === 32
        ) {
          buffer += name[i];
        }
      }

      this.name = buffer.toLowerCase().replace(/\s/gi, '_');

      this.$refs.projectNameInput.update(this.validated);
    },

    async createProject() {
      if (!this.validated) {
        return;
      }

      this.error = null;

      this.creating = true;

      const response = await axios.post('/projects/create', {
        name: this.name,
        type: this.type,
      });

      if (response.status !== 200) {
        this.error = response.data.message;
        this.creating = false;
        this.focusProjectNameInput();
        return;
      }

      this.ADD_PROJECT(response.data.project);

      await this.$router.replace(
        `projects/${response.data.project.uuid}`,
      );
    },
  },
};
</script>

<style scoped></style>
