<template>
  <div
    :class="[`checkbox check-${colorClass}`, { inline: !block, 'no-margin': noMargin }]"
    :value="value"
  >
    <input
      :id="id"
      :ref="id"
      v-model="state"
      :disabled="disabled || (project && project.downloaded)"
      type="checkbox"
      @change="broadcastEvents"
    >
    <label
      :class="['checkbox-label', { centered: centered, 'tiny-right-margin': noMargin }]"
      :for="id"
    >
      <slot name="label">
        <span v-html="label" />
      </slot>

      <slot v-if="!hasLabelSlot && !label">
        <span v-html="label" />
      </slot>
    </label>
  </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
  name: 'PgCheckBox',
  props: {
    value: {},
    noMargin: Boolean,
    colorClass: {
      type: String,
      default: 'info',
    },
    label: {
      type: String,
      default: null,
    },
    centered: Boolean,
    disabled: Boolean,
    block: Boolean,
    ensureState: Boolean,
  },
  data() {
    return {
      id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
      state: this.value !== undefined ? this.value : false,

      hasLabelSlot: false,
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  watch: {
    value: {
      handler(v) {
        this.state = v !== undefined ? v : this.state;
      },
    },
  },
  created() {
    this.hasLabelSlot = (this.$slots && this.$slots.label) !== undefined;
  },
  methods: {
    broadcastEvents() {
      if (this.ensureState && !this.state) {
        this.state = true;

        if (this.$refs[this.id]) {
          this.$refs[this.id].checked = true;
        }

        this.$nextTick(() => {
          this.$emit('input', this.state);
          this.$emit('change', this.state);
        });
      } else {
        this.$emit('input', this.state);
        this.$emit('change', this.state);
      }
    },
  },
};
</script>

<!--suppress CssUnusedSymbol -->
<style>
.tiny-right-margin {
  margin-right: 5px !important;
}
</style>
