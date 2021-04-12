<template>
  <div
    id="unauthenticatedModal"
    class="fade fill-in"
    tabindex="-1"
    role="dialog"
    aria-hidden="true" data-backdrop="static" data-keyboard="false"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-body">
          <h3 class="text-info bold text-center"><i class="fa fa-exclamation-triangle"></i> Session Expired!</h3>
          <p class="text-center text-info">
            Unable to complete the
            requested operation successfully. You were inactive for a while and must login again
            by <a :href="app.baseURL" class="bold text-primary">clicking here</a>.
            Alternatively, you can refresh this page.
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
  name: 'UnauthenticatedModal',
  created() {
    this.$nextTick(() => {
      document.querySelector('#unauthenticatedModal').classList.add('modal');
    });
  },
};
</script>

<style scoped>

</style>
