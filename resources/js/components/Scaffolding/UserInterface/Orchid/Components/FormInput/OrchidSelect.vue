<template>
  <row>
    <column size="6">
      <form-input-title :centered="false" small title="Display Column" />
      <simple-select full-width filterable :entities="columns" v-model="displayColumnId">
        <template slot-scope="{ entity }">
          <el-option :key="entity.id"
                     :label="entity.name"
                     :value="entity.id" />
        </template>
      </simple-select>
    </column>
    <column size="6">
      <form-input-title :centered="false" small title="Value Column" />
      <simple-select full-width filterable :entities="columns" v-model="valueColumnId">
        <template slot-scope="{ entity }">
          <el-option :key="entity.id"
                     :label="entity.name"
                     :value="entity.id" />
        </template>
      </simple-select>
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import SimpleSelect from '@/components/Select/SimpleSelect';

export default {
  name: 'OrchidSelect',
  props: {
    tableName: String,
    column: {},
    columns: {},
    persisted: {},
  },
  components: {
    SimpleSelect,
    FormInputTitle,
    Row,
    Column,
  },
  data() {
    const persisted = this.persisted || {};

    return {
      displayColumnId: persisted.valueColumnId || this.column.id,
      valueColumnId: persisted.valueColumnId || this.column.id,
    };
  },
  created() {
    // TODO: Have the parent set the defaults to avoid the following comparison...
    const persisted = JSON.stringify(this.persisted || {});
    const broadcastable = JSON.stringify(this.broadcastable);

    if (persisted !== broadcastable) {
      this.broadcastChanges(this.broadcastable);
    }
  },
  computed: {
    broadcastable() {
      return {
        valueColumnId: this.valueColumnId,
      };
    },
  },
  watch: {
    broadcastable: {
      handler(v) {
        this.broadcastChanges(v);
      },
    },
  },
  methods: {
    broadcastChanges(changes) {
      this.$emit('updated', changes);
    },
  },
};
</script>

<style scoped>

</style>
