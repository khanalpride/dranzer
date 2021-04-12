<template>
  <div v-if="module && importPending(module)">
    <row>
      <column>
        <p
          v-if="!slowImportResolution"
          class="text-info text-center"
        >
          {{ loadingText }}
        </p>
        <p
          v-if="slowImportResolution && importPending(module)"
          class="text-danger text-center"
        >
          <i class="fa fa-exclamation-triangle" />
          <span
            v-tippy="{ placement: 'bottom', distance: 10 }"
            content="If the loading continues for a few more seconds, try refreshing this page.
            If the problem persists, make sure you are connected to the internet."
          >Loading seems to be taking longer than usual...</span>
        </p>
      </column>
      <column
        size="4"
        offset="4"
      >
        <indeterminate-progress-bar />
      </column>
    </row>
  </div>
</template>

<script>
import asyncImports from '@/mixins/async_imports';

import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';

export default {
  name: 'PendingImportProgress',
  components: { Column, Row, IndeterminateProgressBar },
  mixins: [asyncImports],
  props: {
    module: {
      type: String,
      required: true,
    },
    loadingText: {
      type: String,
      default: 'Loading...',
    },
  },
  data() {
    return {
      slowImportResolution: false,
      slowConnection: false,
    };
  },
  mounted() {
    this.slowImportResolution = false;
    setTimeout(() => {
      if (this.importPending(this.module)) {
        this.slowImportResolution = true;
      }
    }, 10000);
  },
  methods: {},
};
</script>

<style scoped></style>
