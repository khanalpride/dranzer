<template>
  <div class="header">
    <div>
      <div
        class="brand inline m-l-5"
        style="width: 190px; !important"
      >
        <router-link
          class="app-title fs-14 bold"
          to="/"
        >
          <img
            :src="asset('images/logo.png')"
            alt="Dranzer"
            style="margin-top: -7px"
            width="32"
          >
          <span style="margin-left: -6px">
            {{ app.name.toUpperCase() }}
          </span>
        </router-link>
      </div>
      <a
        class="d-lg-inline-block d-none text-primary link m-l-10"
        href="https://github.com/level39/dranzer/issues/new?labels=enhancement"
        target="_blank">
          <i class="fa fa-plus" />
          Feature Requests
      </a>
      <a
        class="d-lg-inline-block d-none text-danger link m-l-10"
        href="https://github.com/level39/dranzer/issues/new?labels=bug"
        target="_blank">
          <i class="fa fa-bug" />
          Issues
      </a>
      <router-link
        class="d-lg-inline-block d-none text-green link m-l-10"
        to="/roadmap">
          <i class="fa fa-road" />
          Roadmap
      </router-link>
    </div>
    <div class="d-flex align-items-center">
      <div class="pull-left p-r-10 fs-14 font-heading d-lg-block d-none">
        <span
          v-tippy="{ placement: 'left' }"
          class="bold hint-text"
          :content="
            `<i class='fa fa-github'></i>
           <span class='text-danger bold'>${user.username}</span>
           <span class='text-complete m-l-5'>${user.email}</span>`
          "
        >{{ user.name }}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NavBar',
  data() {
    return {
      user: window.user,
    };
  },
};
</script>

<style scoped>
.app-version {
  margin-left: 2px;
  margin-top: 6px;
  position: absolute;
}
</style>
