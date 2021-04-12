<template>
  <el-select :class="{'el-sel-full-width': fullWidth}"
             :value="value"
             :filterable="filterable"
             :allow-create="allowCreate"
             :clearable="clearable"
             :collapse-tags="collapseTags"
             :multiple="multiple"
             :disabled="disabled || (project && project.downloaded && !noImplicitDisable)"
             :placeholder="placeholder"
             :no-data-text="noDataText"
             :no-match-text="noMatchText"
             @input="$emit('input', $event)"
             @change="$emit('change', $event)">
    <template v-for="e in entities">
      <slot v-bind:entity="e" />
    </template>
  </el-select>
</template>

<script>
import { mapState } from 'vuex';

export default {
  name: 'SimpleSelect',
  props: {
    value: {},
    entities: Array,
    filterable: Boolean,
    clearable: Boolean,
    collapseTags: Boolean,
    multiple: Boolean,
    allowCreate: Boolean,
    fullWidth: Boolean,
    disabled: Boolean,
    noImplicitDisable: Boolean,
    noDataText: {
      type: String,
      default: 'No available data',
    },
    noMatchText: {
      type: String,
      default: 'No matching data',
    },
    placeholder: {
      type: String,
      default: 'Select...',
    },
  },
  computed: {
    ...mapState('project', ['project']),
  },
};
</script>

<style scoped>

</style>
