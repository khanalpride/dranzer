<template>
  <div
    class="row"
    :class="[push]"
  >
    <slot />
  </div>
</template>

<script>
import grid from '../../../mixins/grid';

export default {
  name: 'Row',
  mixins: [grid],
};
</script>

<style scoped></style>
