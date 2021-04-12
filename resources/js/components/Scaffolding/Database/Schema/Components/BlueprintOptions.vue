<template>
  <row>
    <column size="4" offset="2">
      <pg-check-box
        no-margin
        :value="options.createModel"
        :disabled="mandatoryModel"
        label="Create Model"
        @change="$emit('create-model-toggled', $event)"
      />
      <pg-check-box
        no-margin
        :value="!options.createModel ? false : options.softDelete"
        :disabled="!options.createModel"
        label="Soft Delete"
        color-class="danger"
        @change="$emit('soft-delete-toggled', $event)"
      />
      <pg-check-box
        no-margin
        :value="options.unguarded"
        :disabled="!options.createModel"
        label="Unguarded"
        color-class="danger"
        @change="$emit('unguarded-toggled', $event)"
      />
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';

export default {
  name: 'BlueprintOptions',
  props: {
    options: {},
    mandatoryModel: Boolean,
  },
  components: { PgCheckBox, Column, Row },
};
</script>

<style scoped>

</style>
