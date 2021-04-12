<template>
  <div
    :class="[
      `col-${size}`,
      `${offset ? 'offset-' + offset : ''}`,
      { 'text-center': centered },
      push
    ]"
  >
    <slot />
  </div>
</template>

<script>
import grid from '../../../mixins/grid';

export default {
  name: 'Column',
  mixins: [grid],
};
</script>

<style scoped></style>
