<template>
  <row v-if="initializing">
    <column :size="noCenteredText ? 12: 4" :offset="noCenteredText ? 0 : 4" :centered="!noCenteredText">
      <p class="text-black" v-html="initializerText"></p>
    </column>

    <column size="4" :offset="noCenteredText ? 0 : 4">
      <indeterminate-progress-bar />
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'InitializingProgressContainer',
  components: { IndeterminateProgressBar, Row, Column },
  props: {
    initializing: Boolean,
    noCenteredText: Boolean,
    initializerText: {
      type: String,
      default: 'Initializing...',
    },
  },
};
</script>

<style scoped>

</style>
