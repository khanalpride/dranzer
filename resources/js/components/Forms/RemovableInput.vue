<template>
  <span class="text-center" :style="{'width': `${size}%`, 'margin-left': !validated ? '-1px' : '0px'}">
    <a href="#" class="text-danger small hint-text" @click.prevent="$emit('delete')" v-if="removable">
      <span><i class="fa fa-trash"></i> Delete</span>
    </a>
    <span style="user-select: none;" v-else>&nbsp;</span>
    <slot />
  </span>
</template>

<script>
export default {
  name: 'RemovableInput',
  props: {
    size: {
      type: [Number, String],
      default: 100,
    },
    removable: {
      type: Boolean,
      default: true,
    },
    validated: {
      type: Boolean,
      default: true,
    },
  },
};
</script>

<style scoped>
</style>
