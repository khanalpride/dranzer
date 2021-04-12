<template>
  <div
    class="form-group input-group custom-form-input-group"
    :class="{ compact, 'no-margin': noMargin }"
  >
    <div class="input-group-prepend">
      <slot name="prepend" />
    </div>
    <slot />
  </div>
</template>

<script>
export default {
  name: 'FormInputGroup',
  props: {
    compact: Boolean,
    noMargin: Boolean,
  },
};
</script>

<!--suppress CssUnusedSymbol -->
<style scoped>
.compact {
    margin-bottom: 3px !important;
}
.no-margin {
    margin-bottom: 0 !important;
}
.custom-form-input-group {
  width: 99.9% !important;
  margin-left: 1px !important;
}
</style>
