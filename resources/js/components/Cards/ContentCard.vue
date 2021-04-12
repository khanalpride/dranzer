<template>
  <el-card
    class="el-content-card"
    :class="{ 'minimal-header': minimalHeader }"
    :shadow="forceShadow ? 'always' : shadow"
  >
    <slot name="header">
      <template slot="header">
        <p
          v-if="heading"
          :class="[
            { 'text-center': centered },
            { bold: bold },
            `text-${headingColorClass}`
          ]"
        >
          <span v-html="heading" />

          <a
            v-if="removable"
            href="#"
            class="text-danger m-l-5"
            @click.prevent="$emit('delete')"
          >
            <i class="fa fa-close" />
          </a>

          <slot name="sub-heading" />
        </p>
      </template>
    </slot>

    <template slot="default">
      <row v-if="collapsible && !isVisible">
        <column
          centered
          push10
        >
          <button
            class="btn btn-complete"
            @click="isVisible = true"
          >
            {{ collapsedButtonTitle }}
          </button>
        </column>
      </row>
      <slot v-if="isVisible" />
    </template>
  </el-card>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';

export default {
  name: 'ContentCard',
  components: { Column, Row },
  props: {
    heading: {
      type: String,
      default: null,
    },
    removable: Boolean,
    minimalHeader: Boolean,
    collapsedButtonTitle: {
      type: String,
      default: 'Show Configuration',
    },
    collapsible: Boolean,
    headingColorClass: {
      type: String,
      default: 'info',
    },
    shadowOnInit: {
      type: Boolean,
      default: true,
    },
    shadow: {
      type: String,
      default: 'hover',
    },
    centered: {
      type: Boolean,
      default: true,
    },
    bold: {
      type: Boolean,
      default: true,
    },
    visible: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      forceShadow: false,
      isVisible: this.visible !== undefined ? this.visible : true,
    };
  },
  watch: {
    visible: {
      handler(v) {
        this.isVisible = v !== undefined ? v : true;
      },
    },
  },
  mounted() {
    if (this.shadowOnInit && this.shadow !== 'always') {
      this.forceShadow = true;

      setTimeout(() => {
        this.$nextTick(() => {
          this.forceShadow = false;
        });
      }, 1500);
    }
  },
  methods: {
    collapse() {
      this.isVisible = false;
    },

    expand() {
      this.isVisible = true;
    },

    toggleVisibility() {
      this.isVisible = !this.isVisible;
    },

    broadcastEdit() {
      this.toggleVisibility();
      this.$emit('visibility-changed', this.isVisible);
    },
  },
};
</script>

<!--suppress CssUnusedSymbol -->
<style>
.el-content-card.minimal-header > .el-card__header {
    padding-bottom: 7px !important;
}
</style>
