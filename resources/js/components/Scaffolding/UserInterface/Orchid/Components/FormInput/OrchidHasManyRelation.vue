<template>
  <row>
    <column v-if="!hideInfo">
      <p class="text-info hint-text">
        <i class="fa fa-info"></i>
        The display column is the human-friendly representation of the relation.
        For example, it's displayed as a table column when listing or in a drop-down when creating
        or modifying an entity.
      </p>
    </column>
    <column push10 size="6">
      <form-input-title :centered="false" small :title="`Display Column (${foreignModel.modelName} Model)`" />
      <simple-select full-width :disabled="disabled"
                     filterable
                     :entities="foreignTableColumns"
                     v-model="foreignDisplayColumn">
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
  name: 'OrchidHasManyRelation',
  props: {
    screen: {
      type: String,
      default: 'create',
    },
    tableName: String,
    tableId: [String, Number],
    foreignModel: {},
    blueprints: Array,
    relations: Array,
    column: {},
    columns: {},
    persisted: {},
    disabled: Boolean,
    hideInfo: Boolean,
    hideValueColumn: Boolean,
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
      columnId: persisted.columnId || this.column.id,
      displayColumnId: persisted.displayColumnId || null,
    };
  },
  computed: {
    broadcastable() {
      return {
        screen: this.screen,
        columnId: this.columnId,
        modelId: this.foreignModel.id,
        displayColumnId: this.displayColumnId,
      };
    },
    foreignDisplayColumn: {
      get() {
        const columns = this.foreignModel.columns || [];

        const primaryColumn = columns.find((c) => c.attributes.ai) || {};

        return this.displayColumnId || primaryColumn.id;
      },
      set(value) {
        this.displayColumnId = value;
      },
    },
    foreignTableColumns() {
      return this.foreignModel.columns || [];
    },
  },
  created() {
    // TODO: Have the parent set the defaults to avoid the following comparison...
    const persisted = JSON.stringify(this.persisted || {});
    const broadcastable = JSON.stringify(this.broadcastable);

    if (persisted !== broadcastable) {
      this.broadcastChanges(this.broadcastable);
    }
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
      if (!this.disabled) {
        this.$emit('updated', changes);
      }
    },
  },
};
</script>

<style scoped>

</style>
