<template>
  <p :class="color">
    <i class="fa fa-hand-pointer-o" /> {{ text }}
  </p>
</template>

<script>
export default {
  name: 'PlainTextCallToAction',
  props: {
    color: {
      type: String,
      default: 'text-complete',
    },
    text: {
      type: String,
      required: true,
    },
  },
};
</script>

<style scoped></style>
