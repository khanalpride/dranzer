<template>
  <a :href="url"
     :class="['link', `text-${color}`, {'m-l-10': spaced, small, bold}]"
     target="_blank">
    <i class="fa fa-external-link"></i>
    <slot />
  </a>
</template>

<script>
export default {
  name: 'ExternalLink',
  props: {
    url: String,
    color: {
      type: String,
      default: 'info',
    },
    spaced: {
      type: Boolean,
      default: true,
    },
    small: Boolean,
    bold: Boolean,
  },
};
</script>

<style scoped>

</style>
