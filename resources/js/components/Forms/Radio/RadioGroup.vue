<template>
  <div :value="value">
    <el-radio
      v-for="option in options"
      :key="option.key"
      :disabled="disabled || (project && project.downloaded && !noImplicitDisable)"
      v-model="selected"
      v-tippy="{
        onShow: () => option.tooltip !== undefined,
        placement: tooltipLocation,
        distance: 20,
        delay: [750, null]
      }"
      :name="id"
      :label="option.key"
      :content="option.tooltip"
      @input="broadcastEvents"
    >
      {{ option.name }}
    </el-radio>
  </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
  name: 'RadioGroup',
  props: {
    value: {},
    disabled: Boolean,
    noImplicitDisable: Boolean,
    options: {
      type: Array,
      required: true,
    },
    tooltipLocation: {
      type: String,
      default: 'bottom',
    },
    persist: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      id: `'${Math.round(Math.random() * Number.MAX_SAFE_INTEGER)}'`,
      selected: this.value,
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  watch: {
    value: {
      handler(v) {
        this.selected = v;
      },
      immediate: true,
    },
  },
  methods: {
    broadcastEvents(e) {
      this.$emit('input', e);
      this.$emit('change', e);
    },
  },
};
</script>

<style scoped></style>
