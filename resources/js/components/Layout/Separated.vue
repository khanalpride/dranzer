<template>
  <div>
    <separator v-if="prepend" />
    <slot />
    <separator v-if="append" />
  </div>
</template>

<script>
import Separator from '@/components/Layout/Separator';

export default {
  name: 'Separated',
  props: {
    prepend: Boolean,
    append: {
      type: Boolean,
      default: true,
    },
  },
  components: { Separator },
};
</script>

<style scoped>

</style>
