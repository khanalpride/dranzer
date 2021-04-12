<template>
  <nav
    id="sidebar"
    class="page-sidebar"
    data-pages="sidebar"
  >
    <div class="sidebar-header">
      <router-link
        class="app-title-sidebar fs-14 bold"
        to="/"
      >
        <img
          :src="asset('images/logo.png')"
          :alt="app.name"
          style="margin-top: -7px"
          width="32"
        >
        <span style="margin-left: -6px">
          {{ app.name.toUpperCase() }}
        </span>
      </router-link>
    </div>
    <!-- END SIDEBAR MENU HEADER-->
    <!-- START SIDEBAR MENU -->
    <div class="sidebar-menu">
      <!-- BEGIN SIDEBAR MENU ITEMS-->
      <ul class="menu-items">
        <li
          class="m-t-30"
          :class="{ active: $route.fullPath === '/' }"
        >
          <router-link to="/">
            <span class="title">New Project</span>
          </router-link>
          <span
            class="icon-thumbnail"
            :class="{ 'bg-danger': $route.fullPath === '/' }"
          ><i class="pg-plus" /></span>
        </li>
        <li
          v-if="projects.length"
          :class="{ 'open active': isProjectRoute }"
        >
          <a href="javascript:">
            <span class="title">Projects</span>
            <span
              class="arrow"
              :class="{ open: isProjectRoute }"
            />
          </a>
          <span
            class="icon-thumbnail"
          ><i class="fa fa-list-ul" /></span>
          <ul class="sub-menu">
            <li
              v-if="slicedProject"
              class="active"
            >
              <router-link
                :to="`/projects/${slicedProject.uuid}`"
              >
                <span class="title">{{
                    slicedProject.name
                  }}</span>
              </router-link>
              <span class="icon-thumbnail bg-danger">
                <span
                  v-tippy="{
                    placement: 'top',
                    distance: 20,
                    onShow: () =>
                      getProjectInitials(
                        slicedProject
                      ) === '?'
                  }"
                  content="<span class='v-tippy-error'>Indicates Poor Project Name</span>"
                  v-html="getProjectInitials(slicedProject)"
                />
              </span>
            </li>
            <li
              v-for="project in projects.slice(0, slicedProject ? maxProjects - 1 : maxProjects)"
              :key="project.uuid"
              :class="{
                active: project.uuid === lastRouteSegment
              }"
            >
              <router-link :to="`/projects/${project.uuid}`">
                {{
                  project.name
                }}
              </router-link>
              <span
                class="icon-thumbnail"
                :class="{
                  'bg-danger':
                    project.uuid === lastRouteSegment
                }"
              >
                <span
                  v-tippy="{
                    placement: 'top',
                    distance: 20,
                    onShow: () =>
                      getProjectInitials(project) === '?'
                  }"
                  content="<span class='v-tippy-error'>Indicates Poor Project Name</span>"
                  v-html="getProjectInitials(project)"
                />
              </span>
            </li>
            <li
              v-if="totalProjects > maxProjects"
              :class="{ active: lastRouteSegment === 'projects' }"
            >
              <router-link to="/projects">
                <span class="title">View All Projects</span>
              </router-link>
              <span
                class="icon-thumbnail"
                :class="{
                  'bg-primary':
                    $route.fullPath === '/projects'
                }"
              ><i class="pg-unordered_list" /></span>
            </li>
          </ul>
        </li>
        <li :class="{'active': $route.fullPath === '/mutations'}">
          <router-link to="/mutations" :class="{'active': $route.fullPath === '/mutations'}">
            <span class="title">Mutations</span>
          </router-link>
          <span class="icon-thumbnail" :class="{'bg-danger': $route.fullPath === '/mutations'}">
              <i class="fa fa-exchange" />
          </span>
        </li>
        <li>
          <a :href="`${app.baseURL}/auth/logout`">
            <span class="title">Logout</span>
          </a>
          <span
            class="icon-thumbnail"
          ><i class="fa fa-lock" /></span>
        </li>
      </ul>
      <div class="clearfix" />
    </div>
    <!-- END SIDEBAR MENU -->
  </nav>
</template>

<script>
import { mapState, mapActions, mapMutations } from 'vuex';

const $ = window.jQuery;

export default {
  name: 'Sidebar',
  data() {
    return {
      maxProjects: 5,

      firstRouteSegment: null,
      lastRouteSegment: null,

      slicedProject: null,
    };
  },
  computed: {
    ...mapState('app', [
      'projects',
      'projectsFetchCount',
      'loadingProjects',
      'totalProjects',
    ]),
    ...mapState('project', [
      'project',
    ]),
    isProjectRoute() {
      return this.firstRouteSegment === 'projects';
    },
  },
  watch: {
    $route: {
      async handler(to) {
        this.slicedProject = null;

        const fullPath = to.fullPath.substr(1);
        const segments = fullPath.split('/');

        if (segments.length) {
          // eslint-disable-next-line prefer-destructuring
          this.firstRouteSegment = segments[0];
          this.lastRouteSegment = segments[segments.length - 1];
        }

        await this.getProjects();

        if (this.isProjectRoute) {
          let project = this.projects
            .slice(0, this.maxProjects)
            .find((p) => p.uuid === this.lastRouteSegment);

          if (project) {
            return;
          }

          project = this.projects.slice(this.maxProjects).find(
            (p) => p.uuid === this.lastRouteSegment,
          );

          if (project) {
            this.slicedProject = project;
          }
        }
      },
      immediate: true,
    },
    project: {
      async handler(v) {
        if (v) {
          const project = this.projects
            .find((p) => p.uuid === v.uuid);

          if (!project) {
            return;
          }

          project.name = v.name;
        }
      },
      deep: true,
    },
  },
  async mounted() {
    this.$nextTick(() => {
      $.Pages.initSidebar();
    });
  },
  methods: {
    ...mapActions('app', ['getProjects']),
    ...mapMutations('app', ['SET_PROJECT_VALIDATED']),
    getProjectInitials(project) {
      const name = project.name.replace(/_/gi, '').trim().toUpperCase();

      return name === '' ? '?' : (name[0] + name[name.length - 1]);
    },
  },
};
</script>

<style scoped></style>
