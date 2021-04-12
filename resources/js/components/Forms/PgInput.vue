<template>
  <input
    :ref="id"
    v-tippy="{
      onShow: () =>
        customTooltip ||
        (vTooltip !== undefined && !isValidated),
      placement: vTooltipPlacement,
    }"
    :placeholder="placeholder"
    :value="value"
    :readonly="readOnly"
    :disabled="disabled || (project && project.downloaded && !noImplicitDisable)"
    :spellcheck="spellCheck"
    :content="vTooltip"
    :class="[
      'form-control',
      {
        'invalid-indicator': !isValidated && indicatorStyle === 'normal',
        'minimal-invalid-indicator': !isValidated && indicatorStyle === 'minimal'}
    ]"
    @keydown="onKeyDown"
    @mouseenter="hoveredFocus"
    @input="onInputChanged"
  >
</template>

<script>
import { mapState } from 'vuex';

export default {
  name: 'PgInput',
  props: {
    value: {},
    placeholder: String,
    readOnly: Boolean,
    disabled: Boolean,
    spellCheck: Boolean,
    focusOnHover: {
      type: Boolean,
      default: true,
    },
    indicatorStyle: {
      type: String,
      default: 'minimal',
    },
    validate: Boolean,
    validated: Boolean,
    validationResult: {},
    validationTooltip: String,
    tooltipPlacement: {
      type: String,
      default: 'top',
    },
    customTooltip: Boolean,
    maxLength: Number,
    noImplicitDisable: Boolean,
  },
  data() {
    return {
      id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
      input: '',

      tipPlacement: ((this.validationResult || {}).tipPlacement) || this.tooltipPlacement,
    };
  },
  computed: {
    ...mapState('project', ['project']),

    vTooltip() {
      if (!this.validate || this.validated || (this.validationResult && this.validationResult.passed)) {
        return '';
      }

      const validationResult = (this.validationResult || {});

      const { message } = validationResult;
      const { pattern } = validationResult;

      let tooltip = this.validationTooltip;

      if (!tooltip) {
        tooltip = message || (pattern ? `Must match ${this.str.stylize(`[bc]${validationResult.pattern}[/bc]`)}` : 'Invalid input');
      }

      return `<span class="text-danger bold"><i class="fa fa-exclamation-triangle"></i> ${tooltip}</span>`;
    },

    vTooltipPlacement() {
      return this.tipPlacement || this.tooltipPlacement;
    },

    isValidated() {
      return !this.validate || (this.validationResult && this.validationResult.passed) || this.validated;
    },
  },
  watch: {
    validated: {
      handler(v) {
        let tippy = null;

        this.$nextTick(() => {
          // eslint-disable-next-line no-underscore-dangle
          tippy = this.$refs[this.id] ? this.$refs[this.id]._tippy : null;

          if (!tippy) {
            return;
          }

          if (v) {
            tippy.hide();
          } else {
            tippy.show();
          }
        });
      },
      immediate: true,
    },
    validationResult: {
      handler(v) {
        const passed = (v || {}).passed || null;

        if (passed === null) {
          return;
        }

        let tippy = null;

        this.$nextTick(() => {
          // eslint-disable-next-line no-underscore-dangle
          tippy = this.$refs[this.id] ? this.$refs[this.id]._tippy : null;

          if (!tippy) {
            return;
          }

          if (passed) {
            tippy.hide();
          } else {
            tippy.show();
          }
        });
      },
      immediate: true,
    },
  },
  methods: {
    focus() {
      this.$nextTick(() => {
        if (this.$refs[this.id]) {
          this.$refs[this.id].focus();
        }
      });
    },

    hoveredFocus() {
      this.$emit('mouseenter');
      if (this.focusOnHover) {
        this.focus();
      }
    },

    update() {
      this.$forceUpdate();
    },

    element() {
      return this.$refs[this.id] ? this.$refs[this.id] : null;
    },

    onInputChanged(e) {
      this.input = Number.isInteger(this.maxLength) ? this.str.ellipse(e.target.value, Number(this.maxLength), false) : e.target.value;
      if (this.input !== e.target.value) {
        e.target.value = this.input;
      }
      this.$emit('input', this.input);
      this.$emit('change', this.input);
    },

    onKeyDown(e) {
      const code = e.keyCode;

      if (e.ctrlKey || e.altKey) {
        return;
      }

      if (
        code === 8
        || code === 9
        || (code >= 37 && code <= 40)
      ) {
        return;
      }

      if (Number.isInteger(this.maxLength) && (Number(this.input.length) > Number(this.maxLength))) {
        e.preventDefault();
      }
    },
  },
};
</script>

<!--suppress CssUnusedSymbol -->
<style scoped>
.minimal-invalid-indicator {
    border-left: 2px solid rgba(255, 0, 0, 0.6) !important;
}
.invalid-indicator {
    border: 1px solid rgba(255, 0, 0, 0.6) !important;
}
</style>
