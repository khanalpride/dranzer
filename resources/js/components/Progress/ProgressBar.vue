<template>
  <div class="progress">
    <div
      class="progress-bar"
      :style="`width:${percentage}%`"
    />
  </div>
</template>

<script>
export default {
  name: 'ProgressBar',
  props: {
    percentage: {
      type: [String, Number],
      default: null,
    },
  },
};
</script>

<style scoped></style>
