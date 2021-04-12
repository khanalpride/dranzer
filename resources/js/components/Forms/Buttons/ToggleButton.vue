<template>
  <button
    v-tippy="{
      onShow: () => !disableTooltips,
      placement: tooltipPlacement,
      distance: 15,
      delay: [tooltipDelay, null]
    }"
    :class="[
      `btn btn-${state ? onColorClass : offColorClass}`,
      { 'btn-sm': small }
    ]"
    :disabled="disabled || (project && project.downloaded && !noImplicitDisable)"
    :value="value"
    :content="formattedTooltip"
    @click="toggleState"
  >
    <i
      v-if="!hideIcon"
      :class="{
        'fa fa-close': !state,
        'fa fa-check': state,
        'fa fa-paper-plane': mutating
      }"
      :style="[
        !state
          ? {
            width:
              '12.02px' /* fa-close is 3 pixels smaller than fa-check in width*/
          }
          : {}
      ]"
    />
    <slot>
      <span v-if="text && !icon">{{ text }}</span>
      <span v-else><i :class="icon" /></span>
    </slot>
  </button>
</template>

<script>
import mutations from '@/mixins/mutations';
import { mapState } from 'vuex';

export default {
  name: 'ToggleButton',
  mixins: [mutations],
  props: {
    value: {},
    text: {
      type: String,
      default: null,
    },
    icon: {
      type: String,
      default: null,
    },
    onColorClass: {
      type: String,
      default: 'green',
    },
    offColorClass: {
      type: String,
      default: 'info',
    },
    tooltipPlacement: {
      type: String,
      default: 'bottom',
    },
    hideIcon: {
      type: Boolean,
      default: true,
    },
    disableOff: Boolean,
    small: Boolean,
    disableTooltips: Boolean,
    disabled: Boolean,
    stateTooltipSuffix: {
      type: String,
      default: null,
    },
    path: {
      type: String,
      default: null,
    },
    tooltipDelay: {
      type: Number,
      default: 750,
    },
    tooltip: {},
    noImplicitDisable: Boolean,
  },
  data() {
    return {
      state: this.value !== undefined ? this.value : false,

      mutationName: null,
      mutationPath: null,
    };
  },
  computed: {
    ...mapState('project', ['project']),

    formattedTooltip() {
      if (this.tooltip && this.tooltip.enabled && this.tooltip.disabled) {
        return this.state === true
          ? this.tooltip.enabled
          : this.tooltip.disabled;
      }

      const suffix = this.stateTooltipSuffix || this.text;

      if (suffix) {
        return this.state === true
          ? `Disable ${suffix}`
          : `Enable ${suffix}`;
      }

      return this.state === true ? 'Disable' : 'Enable';
    },
  },
  watch: {
    value: {
      handler(v) {
        this.state = v;
      },
      immediate: true,
    },
    state: {
      handler(v) {
        if (this.disableOff && !v) {
          return;
        }

        this.$emit('change', v);
        this.$emit('input', v);
        this.$emit('update:state', v);
      },
    },
  },
  created() {
    this.prepareMutationData();
  },
  methods: {
    async toggleState() {
      if (this.disableOff && this.state) {
        return;
      }

      this.state = !this.state;

      if (!this.mutationPath) {
        return;
      }

      await this.mutate(this.state);
    },
  },
};
</script>

<style scoped></style>
