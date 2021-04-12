<template>
  <el-select
    v-model="level"
    @change="$emit('change', $event)"
  >
    <el-option
      v-for="lvl in levels"
      :key="lvl"
      :label="lvl.substr(0, 1).toUpperCase() + lvl.substr(1)"
      :value="lvl"
    />
  </el-select>
</template>

<script>
export default {
  name: 'LevelSelector',
  props: {
    default: {},
  },
  data() {
    return {
      level: this.default || 'info',
      levels: [
        'info',
        'debug',
        'alert',
        'critical',
        'emergency',
        'error',
        'warning',
        'notice',
      ],
    };
  },
};
</script>

<style scoped></style>
