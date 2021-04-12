<template>
  <div class="page-container">
    <unauthenticated-modal />
    <request-limit-exceeded-modal />
    <nav-bar />
    <div class="page-content-wrapper content-wrapper">
      <div class="content m-t-10">
        <div class="container-fluid container-fixed-lg">
          <router-view />
        </div>
      </div>
      <app-footer />
    </div>
  </div>
</template>

<script>
import NavBar from '@/components/App/Partials/NavBar';
import AppFooter from '@/components/App/Partials/AppFooter';
import RequestLimitExceededModal from '@/components/Modals/RequestLimitExceededModal';
import UnauthenticatedModal from '@/components/Modals/UnauthenticatedModal';

export default {
  name: 'PageContainer',
  components: {
    UnauthenticatedModal, RequestLimitExceededModal, AppFooter, NavBar,
  },
};
</script>

<style scoped>
.content-wrapper {
    min-height: 92% !important;
}
</style>
