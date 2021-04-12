<template>
  <row :class="[push]" v-if="!loading">
    <column :class="{ 'text-center': centered }" :size="colSize" :offset="colOffset">
      <slot/>
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import grid from '@/mixins/grid';

export default {
  name: 'ContentContainer',
  props: {
    loading: Boolean,
    colSize: [String, Number],
    colOffset: [String, Number],
  },
  mixins: [grid],
  components: { Column, Row },
};
</script>

<style scoped>

</style>
