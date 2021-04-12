<template>
  <tippy
    style="display: inline-flex !important"
  >
    <template slot="trigger">
      <span class="text-danger bold">
        <i class="fa fa-question-circle" />
      </span>
    </template>
    <span
      v-if="content"
      v-html="content"
    />
    <slot v-else />
  </tippy>
</template>

<script>
export default {
  name: 'InlineHelpLink',
  props: {
    content: {
      type: String,
      default: null,
    },
    placement: {
      type: String,
      default: 'top',
    },
  },
};
</script>

<style scoped></style>
