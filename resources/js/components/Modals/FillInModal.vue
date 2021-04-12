<template>
  <div
    :id="id"
    class="modal fade fill-in"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
  >
    <button
      v-if="dismissible"
      type="button"
      class="close"
      data-dismiss="modal"
      aria-hidden="true"
    >
      <i class="pg-close" />
    </button>
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <slot name="header" />
        </div>
        <div class="modal-body">
          <slot />
        </div>
        <div class="modal-footer">
          <slot name="footer" />
        </div>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
</template>

<script>
const $ = window.jQuery;

export default {
  name: 'FillInModal',
  props: {
    dismissible: Boolean,
  },
  data() {
    return {
      id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
    };
  },
  methods: {
    show() {
      this.$nextTick(() => {
        const options = {};

        if (!this.dismissible) {
          options.backdrop = 'static';
          options.keyboard = false;
        }

        $(`#${this.id}`).modal(options, 'show');
      });
    },

    hide() {
      this.$nextTick(() => {
        $(`#${this.id}`).modal('hide');
      });
    },
  },
};
</script>

<style scoped></style>
