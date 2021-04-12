<template>
  <simple-select
    :filterable="filterable"
    :multiple="multiple"
    :collapse-tags="collapseTags"
    :full-width="fullWidth"
    :clearable="clearable"
    :disabled="disabled"
    auto-complete="on"
    placeholder="Select columns..."
    no-data-text="No columns found"
    no-match-text="No matching columns found"
    :value="value"
    :entities="columns"
    @input="$emit('input', $event)"
    @change="$emit('change', $event)">
    <template slot-scope="{ entity }">
      <el-option :key="entity.id"
                 :value="entity.id"
                 :label="entity.name">
        <template>
          <span class="m-r-30">{{ entity.name }}</span>
          <span class="text-success pull-right m-r-15">{{ entity.type || 'No Type' }}</span>
        </template>
      </el-option>
    </template>
  </simple-select>
</template>

<script>
import SimpleSelect from '@/components/Select/SimpleSelect';

export default {
  name: 'SelectableTableColumns',
  components: { SimpleSelect },
  props: {
    value: {},
    columns: Array,
    filterable: Boolean,
    clearable: Boolean,
    collapseTags: Boolean,
    multiple: Boolean,
    fullWidth: Boolean,
    disabled: Boolean,
  },
};
</script>

<style scoped>

</style>
