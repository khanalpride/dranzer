<template>
  <div class="container-fluid container-fixed-lg footer">
    <div class="copyright sm-text-center">
      <p class="small no-margin pull-left sm-pull-reset p-b-10">
          <span>
              <a :href="`https://github.com/level39/${repoName}`" target="_blank">
                  <i class="fa fa-github"></i> GitHub
              </a>
          </span>
      </p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AppFooter',
  data() {
    return {
      repoName: 'dranzer',
    };
  },
};
</script>

<style scoped>
.separator {
    color: lightgray;
    opacity: 0.9;
}
</style>
