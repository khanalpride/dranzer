import Vue from 'vue';
import VueRouter from 'vue-router';

import CreateNewProject from '@/views/Project/CreateNewProject';
import ListProjects from '@/views/Project/ListProjects';
import RoadmapContainer from '@/views/Roadmap/RoadmapContainer';
import ViewProject from '@/views/Project/ViewProject';
import MutationsViewer from '@/components/Debugging/MutationsViewer';

Vue.use(VueRouter);

export default new VueRouter({
  routes: [
    {
      path: '/',
      component: CreateNewProject,
      meta: {
        title: 'New Project',
      },
    },
    {
      path: '/projects',
      component: ListProjects,
      meta: {
        title: 'My Projects',
      },
    },
    {
      path: '/projects/:uuid/:module?',
      component: ViewProject,
    },
    {
      path: '/roadmap',
      component: RoadmapContainer,
    },
    {
      path: '/mutations',
      component: MutationsViewer,
    },
  ],
});
