<template>
  <div
    id="slowDownModal"
    class="fade fill-in"
    tabindex="-1"
    role="dialog"
    aria-hidden="true" data-backdrop="static" data-keyboard="false"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-body">
          <h3 class="text-danger bold text-center"><i class="fa fa-exclamation-triangle"></i> Slow Down!!!</h3>
          <p class="text-center text-danger">
            <span id="slowDownModalMessage">
              You've made too many concurrent requests recently.
              Please wait for a few seconds and then try again.
              <span class="bold">Your last change was not saved.</span>
            </span>
          </p>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
</template>

<script>
export default {
  name: 'RequestLimitExceededModal',
  created() {
    this.$nextTick(() => {
      document.querySelector('#slowDownModal').classList.add('modal');
    });
  },
};
</script>

<style scoped>

</style>
