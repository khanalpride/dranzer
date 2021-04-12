<template>
  <button class="btn"
          :class="[`btn-${colorClass}`, {'btn-max-height' : maxHeight, 'btn-sm': small}]"
          :disabled="disabled || (project && project.downloaded && !noImplicitDisable)"
          @click="$emit('click')" >
    <slot />
  </button>
</template>

<script>
import { mapState } from 'vuex';

export default {
  name: 'SimpleButton',
  props: {
    colorClass: {
      type: String,
      default: 'info',
    },
    disabled: Boolean,
    noImplicitDisable: Boolean,
    small: Boolean,
    maxHeight: Boolean,
  },
  computed: {
    ...mapState('project', ['project']),
  },
};
</script>

<style scoped>

</style>
