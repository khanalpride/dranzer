<template>
  <p class="no-margin"
     :class="[{'p-b-5': !noBottomPadding, 'text-center': centered, 'hint-text': hinted, bold, small}, prefixedColorClass]">
    <slot>
      <span v-html="title"></span>
    </slot>
  </p>
</template>

<script>
export default {
  name: 'FormInputTitle',
  props: {
    title: String,
    bold: {
      type: Boolean,
      default: true,
    },
    centered: {
      type: Boolean,
      default: true,
    },
    colorClass: {
      type: String,
      default: 'info',
    },
    hinted: {
      type: Boolean,
      default: true,
    },
    small: Boolean,
    noBottomPadding: Boolean,
  },
  computed: {
    prefixedColorClass() {
      return `text-${this.colorClass}`;
    },
  },
};
</script>

<style scoped>

</style>
