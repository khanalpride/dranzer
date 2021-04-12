<template>
  <content-card
    :ref="id"
    :heading="heading"
    :collapsible="collapsible"
    :visible="!collapsed"
    @visibility-changed="$emit('visibility-changed')"
  >
    <div v-if="loading">
      <row>
        <column
          size="4"
          offset="4"
        >
          <p class="text-center">
            Restoring Configuration...
          </p>
          <indeterminate-progress-bar />
        </column>
      </row>
    </div>

    <div v-else>
      <slot />
    </div>
  </content-card>
</template>

<script>
import ContentCard from '@/components/Cards/ContentCard';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'ScaffoldingComponentContainer',
  components: {
    IndeterminateProgressBar, Column, Row, ContentCard,
  },
  props: {
    heading: {
      type: String,
      default: null,
    },
    loading: Boolean,
    collapsible: Boolean,
    collapsed: Boolean,
  },
  data() {
    return {
      id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
    };
  },
  methods: {
    collapse() {
      if (this.$refs[this.id]) {
        this.$refs[this.id].collapse();
      }
    },

    toggleVisibility() {
      if (this.$refs[this.id]) {
        this.$refs[this.id].toggleVisibility();
      }
    },
  },
};
</script>

<style scoped></style>
