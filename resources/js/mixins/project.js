import { mapState } from 'vuex';

export default {
  computed: {
    ...mapState('project', ['project']),

    projectId() {
      return this.project ? this.project.uuid : null;
    },
  },
};
