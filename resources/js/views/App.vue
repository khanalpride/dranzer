<template>
  <div id="app-container">
    <fill-in-modal ref="availabilityModal">
      <p class="text-center text-danger">
        <i class="fa fa-exclamation-triangle" /> <app-name /> is not
        available for smaller resolutions at this time.
      </p>
    </fill-in-modal>
    <sidebar v-if="available" />
    <page-container v-if="available" />
  </div>
</template>

<script>
import { mapActions } from 'vuex';

import Sidebar from '@/components/App/Partials/Sidebar';
import FillInModal from '@/components/Modals/FillInModal';
import AppName from '@/components/Typography/Decorated/AppName';
import PageContainer from '@/components/App/Partials/PageContainer';

const $ = window.jQuery;
const { axios } = window;

export default {
  name: 'App',
  components: {
    PageContainer,
    AppName,
    FillInModal,
    Sidebar,
  },
  computed: {
    available() {
      return window.innerWidth >= 1650;
    },
  },
  watch: {
    $route: {
      handler(to) {
        if (to.meta && to.meta.title) {
          document.querySelector('title').innerHTML = `${to.meta.title} - ${this.app.name}`;
        }
      },
      immediate: true,
    },
  },
  async mounted() {
    if (!this.available) {
      this.$nextTick(() => {
        this.$refs.availabilityModal.show();
      });
      return;
    }

    const sidebar = $('#sidebar');
    const pageContainer = $('.page-container');

    if (!sidebar.attr('mounted')) {
      const body = $('body');

      body.prepend(sidebar);

      body.prepend(pageContainer);

      sidebar.attr('mounted', 'true');

      $('#app-container').remove();
    }

    if (this.$route.fullPath === '/projects') {
      return;
    }

    await this.getProjects({ limit: 5 });
  },
  methods: {
    ...mapActions('app', ['getProjects']),
  },
};
</script>

<style></style>
