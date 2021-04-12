<template>
  <hr class="lighter-hr" :class="{compact}">
</template>

<script>
export default {
  name: 'Separator',
  props: {
    compact: Boolean,
  },
};
</script>

<!--suppress CssUnusedSymbol -->
<style scoped>
.compact {
  margin-top: 12px !important;
  margin-bottom: 0 !important;
}
</style>
