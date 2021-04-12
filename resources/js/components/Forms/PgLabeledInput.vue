<template>
  <div
    :class="validationClasses"
    :value="value"
    class="form-group form-group-default"
    @mouseenter="hoveredFocus"
  >
    <!--suppress JSIncompatibleTypesComparison -->
    <label v-html="formattedLabel" />
    <input
      :ref="id"
      v-model="input"
      v-tippy="{
        onShow: () => canShowTooltip,
        placement: tooltipPlacement,
        distance: tooltipDistance,
        interactive: interactiveValidationTooltip,
        delay: [tooltipType === 'validation' ? 275 : tooltipDelay, null]
      }"
      :content="t"
      :disabled="disabled || fetching || (project && project.downloaded && !noImplicitDisable)"
      :placeholder="placeholder"
      :spellcheck="spellCheck"
      class="form-control"
      type="text"
      @input="inputChanged($event)"
      @keydown="$emit('keydown', $event)"
      @keypress="$emit('keypress', $event)"
      @keyup="$emit('keyup', $event)"
    >
  </div>
</template>

<script>
/* eslint-disable no-underscore-dangle */
import { mapState } from 'vuex';
import tooltips from '../../data/ux/tooltips';
import mutations from '../../mixins/mutations';
import project from '../../mixins/project';

export default {
  name: 'PgLabeledInput',
  mixins: [mutations, project],
  props: {
    value: {},
    label: {
      type: String,
    },
    placeholder: {
      type: String,
    },
    spellCheck: Boolean,
    emptyIsValid: Boolean,
    required: Boolean,
    validate: Boolean,
    validated: Boolean,
    indicateValidationSuccessOnLabel: Boolean,
    validationTooltip: {
      type: String,
    },
    interactiveValidationTooltip: Boolean,
    tooltipDistance: {
      type: Number,
      default: 20,
    },
    tooltipDelay: {
      type: Number,
      default: 750,
    },
    tooltipPlacement: {
      type: String,
      default: 'bottom',
    },
    showCharCount: Boolean,
    focusOnHover: {
      type: Boolean,
      default: true,
    },
    processing: Boolean,
    processingIcon: {
      type: String,
      default: null,
    },
    processingIconColor: {
      type: String,
      default: 'complete',
    },
    showProcessingEllipses: Boolean,
    showTooltips: {
      type: Boolean,
      default: true,
    },
    disabled: Boolean,
    noImplicitDisable: Boolean,
    tooltip: {
      type: String,
    },
    tooltipPath: {
      type: String,
    },
    showTooltipsOnRender: Boolean,
    path: {
      type: String,
    },
    fixAvailable: Boolean,
    fixDescription: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),

      input: this.value || '',

      formattedLbl: this.label,
      tickHandle: null,
      ticks: 0,

      mutationHandle: null,
      mutatedHandle: null,

      mutationCount: 0,

      mutationName: null,
      mutationPath: null,

      showTooltips_: false,

      fetching: false,
    };
  },
  computed: {
    ...mapState('project', ['project']),

    formattedLabel() {
      let label = this.formattedLbl;

      if (this.fetching) {
        return `<span class="text-primary"><i class="fa fa-arrow-down"></i> ${label}...</span>`;
      }

      if (this.processing && this.processingIcon) {
        label = `<span class="text-${this.processingIconColor}"><i class="${this.processingIcon}"></i> </span>${label}`;
      }

      if (this.validate && !this.isEmpty) {
        if (this.validated) {
          label = `<span class="${this.processing ? `text-${this.processingIconColor}` : 'text-green'}">${label}</span>`;
        } else {
          label = `<span class="text-danger">${label}</span>`;
        }
      }

      if (
        !this.isEmpty
                && this.indicateValidationSuccessOnLabel
                && this.validate
                && this.validated
      ) {
        label = `<span><i class="fa fa-check text-green"></i></span> ${label}`;
      }

      if (this.showCharCount && this.value && this.value.length > 0) {
        label += `<span class="pull-right">${this.value.length}</span>`;
      }

      return label;
    },

    isEmpty() {
      return (
        !this.value
                || (this.value && this.value.toString().trim() === '')
      );
    },

    validationClasses() {
      if (this.emptyIsValid && this.isEmpty) {
        return {};
      }

      return {
        required: this.required && this.isEmpty,
        'invalid-indicator':
                    (this.required && this.isEmpty && !this.validate)
                    || (this.validate && !this.validated),
        'valid-indicator':
                    (this.required && !this.isEmpty && !this.validate)
                    || (this.validate && this.validated),
      };
    },

    canShowTooltip() {
      return (this.showTooltips_
        && (this.tooltip
          || (this.validationTooltip !== undefined
            && this.validate
            && !this.validated)))
        || this.tooltipPath !== undefined;
    },

    tooltipType() {
      return this.validate && !this.validated && this.validationTooltip
        ? 'validation'
        : 'info';
    },

    t() {
      if (!this.validationTooltip && !this.tooltipPath && !this.tooltip) {
        return '';
      }

      if (this.tooltip) {
        return this.tooltip;
      }

      if (this.validate && !this.validated) {
        return `<span class="v-tippy-error"><i class="fa fa-exclamation-triangle"></i> <span>${this.validationTooltip}</span></span>`;
      }

      if (this.tooltipPath) {
        const path = this.tooltipPath;

        if (path.indexOf('.') < 0) {
          return tooltips[path];
        }

        const segments = path.split('.');

        let tooltip = tooltips;

        // eslint-disable-next-line no-return-assign
        segments.forEach((seg) => (tooltip = tooltip[seg]));

        return tooltip;
      }

      return '';
    },
  },
  watch: {
    value: {
      handler(v) {
        this.input = v || '';
      },
      immediate: true,
    },
    label: {
      handler(v) {
        this.formattedLbl = v;
      },
      immediate: true,
    },
    processing: {
      handler(n) {
        if (!this.showProcessingEllipses) {
          return;
        }

        if (n) {
          this.tickHandle = setInterval(() => {
            let label = this.formattedLbl;

            if (this.ticks > 2) {
              label = label.substr(0, label.length - 3);
              this.ticks = 0;
              this.formattedLbl = label;
              return;
            }

            label += '.';

            this.formattedLbl = label;

            this.ticks += 1;
          }, 500);
        } else if (this.tickHandle) {
          clearInterval(this.tickHandle);

          if (this.ticks > 0) {
            this.formattedLbl = this.formattedLbl.substr(
              0,
              this.formattedLbl.length - this.ticks,
            );
            this.ticks = 0;
          }
        }
      },
      immediate: true,
    },
    validated: {
      handler(v) {
        if (!v) {
          this.$nextTick(() => {
            this.showTooltip();
          });
        } else {
          this.$nextTick(() => {
            this.hideTooltip();
          });
        }
      },
    },
  },
  async created() {
    this.prepareMutationData(this.path);
  },
  mounted() {
    if (!this.showTooltipsOnRender) {
      setTimeout(() => {
        this.showTooltips_ = true;
      }, 1000);
    } else {
      this.showTooltips_ = true;
    }
  },
  methods: {
    focus() {
      this.$nextTick(() => {
        if (this.$refs[this.id]) {
          this.$refs[this.id].focus();
        }
      });
    },

    select() {
      this.$nextTick(() => {
        if (this.$refs[this.id]) {
          this.$refs[this.id].select();
        }
      });
    },

    hoveredFocus() {
      if (this.focusOnHover) {
        this.focus();
      }
    },

    update(validated) {
      this.$forceUpdate();

      if (this.validate) {
        this.updateTooltip(validated);
      }
    },

    updateTooltip(validated) {
      if (validated) {
        this.hideTooltip();
      } else {
        this.showTooltip();
      }
    },

    hideTooltip(force = true) {
      if (!force && this.validate && !this.validated) {
        return;
      }

      this.$nextTick(() => {
        const el = this.$refs[this.id];
        if (el && el._tippy) {
          el._tippy.hide();
        }
      });
    },

    showTooltip() {
      this.$nextTick(() => {
        const el = this.$refs[this.id];
        if (el && el._tippy) {
          el._tippy.show();
        }
      });
    },

    element() {
      return this.$refs[this.id];
    },

    async inputChanged(e) {
      const { value } = e.target;

      this.$emit('input', value);
      this.$emit('update:input', value);

      this.hideTooltip();

      if (!this.mutationPath) {
        return;
      }

      await this.mutate(value);
    },
  },
};
</script>

<!--suppress CssUnusedSymbol -->
<style scoped>
.invalid-indicator {
    border-left: solid 2px #f35958;
}

.valid-indicator {
    border-left: solid 2px #0ea50e;
}

.form-group-default.invalid-indicator.focused {
    border-left: solid 2px #f35958 !important;
}

.form-group-default.valid-indicator.focused {
    border-left: solid 2px #0ea50e !important;
}
</style>
