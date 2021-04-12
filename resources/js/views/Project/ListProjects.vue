<template>
  <div>
    <div
      v-if="!loadingProjects && !projects.length && !inline"
      class="row"
    >
      <div class="col-4 offset-4 text-center">
        <div class="col-12">
          <p class="text-primary">
            <i class="fa fa-info" /> No projects to show at this
            time.
          </p>
        </div>

        <div class="col-12 m-t-20">
          <router-link
            class="btn btn-primary btn-sm"
            to="/"
          >
            <i class="fa fa-plus" /> Create New Project
          </router-link>
        </div>
      </div>
    </div>
    <div class="row">
      <div
        v-if="loadingProjects"
        class="col-4 offset-4"
      >
        <p
          :class="{ 'text-center': !inline }"
          class="text-primary"
        >
          Fetching Projects...
        </p>
        <indeterminate-circular-progress
          :centered="!inline"
          color="primary"
        />
      </div>
    </div>
    <div
      v-if="!loadingProjects && projects.length"
      class="row"
    >
      <div class="col-4 offset-4">
        <div class="row">
          <div class="col-12">
            <p class="text-primary bold">
              My Projects ({{
                inline && totalProjects > 5
                  ? `5 / ${totalProjects}`
                  : totalProjects
              }})
              <span
                class="m-l-5"
              ><a
                v-if="projects.length > 1"
                class="text-complete small"
                href="#"
                @click.prevent="toggleNewestFirst"
              ><i
                v-tippy="{
                  placement: 'right',
                  distance: 20
                }"
                :content="
                  toggleProjectsOrderButtonTooltip
                "
                class="fa fa-sort"
              /></a></span>
            </p>
            <separator />
          </div>
          <div class="col-12 m-t-10">
            <el-timeline>
              <slide-y-up-transition
                :duration="200"
                group
              >
                <el-timeline-item
                  v-for="project in inline
                    ? projects.slice(0, 5)
                    : projects"
                  :key="project.uuid"
                  color="dodgerblue"
                  hide-timestamp
                  class="tailed"
                  placement="top"
                >
                  <div class="row">
                    <div class="col-12">
                      <p
                        class="hint-text text-info small"
                      >
                        {{ project.formattedCreatedTS }}
                      </p>
                    </div>
                    <div class="col-12">
                      <router-link
                        v-if="!isDeleting(project)"
                        v-tippy="{
                          placement: 'left',
                          distance: 15
                        }"
                        :to="`/projects/${project.uuid}`"
                        class="text-primary"
                        content="Open Project"
                      >
                        <i class="fa fa-wrench" />
                        <span class="m-l-5">{{
                          project.name
                        }}</span>
                      </router-link>
                      <a
                        v-if="!isDeleting(project)"
                        v-tippy="{
                          placement: 'right',
                          distance: 20
                        }"
                        class="text-danger m-l-10"
                        content="Delete Project"
                        href="#"
                        @click.prevent="
                          deleteProject(project)
                        "
                      >
                        <i class="fa fa-close" />
                      </a>
                      <span
                        v-if="isDeleting(project)"
                        class="text-primary"
                      >
                        <i class="fa fa-wrench" />
                        <span class="m-l-5">{{
                          project.name
                        }}</span>
                      </span>
                      <span
                        v-if="isDeleting(project)"
                        class="text-danger bold m-l-10 small"
                      >Deleting...</span>
                    </div>
                  </div>
                </el-timeline-item>
              </slide-y-up-transition>
            </el-timeline>
          </div>

          <div
            v-if="totalProjects > 5 && inline"
            class="col-12 m-l-40"
          >
            <router-link
              class="text-primary bold"
              to="/projects"
            >
              View All {{ totalProjects }} Projects
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<!--suppress NpmUsedModulesInstalled -->
<script>
import { mapActions, mapMutations, mapState } from 'vuex';

import { SlideYUpTransition } from 'vue2-transitions';
import IndeterminateCircularProgress from '@/components/Progress/IndeterminateCircularProgress';

import Separator from '@/components/Layout/Separator';

const { axios } = window;

export default {
  name: 'ListProjects',
  components: {
    Separator,
    IndeterminateCircularProgress,
    SlideYUpTransition,
  },
  props: {
    inline: Boolean,
  },
  data() {
    return {
      newestFirst: false,
      deleting: [],
    };
  },
  computed: {
    ...mapState('app', [
      'projects',
      'totalProjects',
      'loadingProjects',
      'projectsOrder',
    ]),

    toggleProjectsOrderButtonTooltip() {
      return this.projectsOrder === 'desc'
        ? 'Show Oldest First'
        : 'Show Newest First';
    },
  },
  async mounted() {
    if (!this.inline) {
      await this.getProjects();
    }
  },
  methods: {
    ...mapActions('app', ['getProjects', 'toggleProjectsOrder']),
    ...mapMutations('app', ['REMOVE_PROJECT', 'ADD_PROJECT']),

    async deleteProject(project) {
      this.deleting.push(project.uuid);
      const { status } = await axios.delete(`/projects/${project.uuid}`);
      this.deleting.splice(this.deleting.indexOf(project.uuid), 1);

      if (status === 204) {
        this.REMOVE_PROJECT(project);
        this.$emit('deleted', project);

        if (!this.projects.length) {
          await this.$router.replace('/');
        }
      }
    },

    isDeleting(project) {
      return this.deleting.indexOf(project.uuid) > -1;
    },

    toggleNewestFirst() {
      this.toggleProjectsOrder({
        name: 'projectsOrder',
        value: this.projectsOrder === 'desc' ? 'asc' : 'desc',
      });
    },
  },
};
</script>

<style scoped></style>
