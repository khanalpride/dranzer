<template>
  <div
    :id="id"
    class="modal fade stick-up"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header clearfix text-left">
          <button
            v-if="dismissible"
            type="button"
            class="close"
            data-dismiss="modal"
            aria-hidden="true"
          >
            <i class="pg-close fs-14" />
          </button>
        </div>
        <div class="modal-body">
          <slot name="header" />
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
const $ = window.jQuery;

export default {
  name: 'StickUpModal',
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
      const options = {};

      if (!this.dismissible) {
        options.backdrop = 'static';
        options.keyboard = false;
      }

      this.$nextTick(() => {
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
