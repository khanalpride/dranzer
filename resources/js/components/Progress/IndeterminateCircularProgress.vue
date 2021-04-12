<template>
  <div
    class="progress-circle-indeterminate"
    :style="`width: ${width}px !important; ${
      centered ? 'margin: 0 auto !important;' : ''
    }`"
    :class="`progress-circle-${color}`"
  />
</template>

<script>
export default {
  name: 'IndeterminateCircularProgress',
  props: {
    color: {
      type: String,
      default: 'info',
    },
    width: {
      type: Number,
      default: 25,
    },
    centered: Boolean,
  },
};
</script>

<style scoped></style>
