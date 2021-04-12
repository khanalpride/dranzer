import Vue from 'vue';
import VueX from 'vuex';

import app from '@/store/modules/app';
import project from '@/store/modules/project';

Vue.use(VueX);

export default new VueX.Store({
  modules: {
    app,
    project,
  },
});
