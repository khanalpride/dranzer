<template>
  <el-select class="el-sel-full-width"
             placeholder="Select models..."
             multiple
             collapse-tags
             filterable
             no-data-text="No Models Found"
             no-match-text="No Models Found"
             :value="value"
             :disabled="disabled || (project && project.downloaded && !noImplicitDisable)"
             @input="$emit('input', $event)"
             @change="$emit('change', $event)">
    <el-option :label="model.modelName"
               :value="model.id"
               :key="model.id"
               v-for="model in filteredModels" />
  </el-select>
</template>

<script>
import { mapState } from 'vuex';

export default {
  name: 'SelectableModels',
  props: {
    value: {},
    models: Array,
    showAll: Boolean,
    disabled: Boolean,
    noImplicitDisable: Boolean,
  },
  computed: {
    ...mapState('project', ['project']),

    filteredModels() {
      if (!this.models) {
        return [];
      }

      return this.showAll ? this.models : this.models.filter((m) => m.visible !== false);
    },
  },
};
</script>

<style scoped>

</style>
