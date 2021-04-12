<template>
  <p :class="[textColor, push, {'hint-text': hinted, 'no-margin': noMargin, bold}]">
    <i class="fa fa-info" v-if="info"></i>
    <i class="fa fa-exclamation-triangle" v-if="danger"></i>
    <slot />
  </p>
</template>

<script>
import grid from '@/mixins/grid';

export default {
  name: 'TextBlock',
  mixins: [grid],
  props: {
    info: Boolean,
    danger: Boolean,
    hinted: Boolean,
    bold: Boolean,
    noMargin: {
      type: Boolean,
      default: true,
    },
    color: {
      type: String,
      default: 'info',
    },
  },
  computed: {
    textColor() {
      return this.danger ? 'text-danger' : `text-${this.color}`;
    },
  },
};
</script>

<style scoped>

</style>
