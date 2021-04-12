<template>
  <div>
    <row v-if="!relation || !foreignModel">
      <column>
        <p class="text-danger p-r-50">
          <i class="fa fa-exclamation-triangle"></i>
          Please select a different <span class="bold text-primary hint-text">Input Type</span>
          as no compatible column were found for this relation. If you would like to you this column as a relation,
          <span class="bold">make sure the foreign key is created properly</span> on the selected table.
        </p>
      </column>
    </row>

    <row v-else>
      <column v-if="!hideInfo">
        <p class="text-info hint-text">
          <i class="fa fa-info"></i>
          The display column is the human-friendly representation of the relation.
          For example, it's displayed as a table column when listing or in a drop-down when creating
          or modifying an entity.
        </p>
        <p class="text-info hint-text">
          <i class="fa fa-info"></i> The value column is used to connect the
          <span class="text-primary bold">{{ model.modelName }}</span>
          with the <span class="text-primary bold">{{ foreignModel.modelName }}</span>.
        </p>
        <p class="text-danger hint-text">
          <i class="fa fa-exclamation-triangle"></i>
          Requires the relation(s) to be present on the models (Use the Eloquent Relations module to create relations).
        </p>
      </column>
      <column push10 size="6">
        <form-input-title :centered="false" small :title="`Display Column (${foreignModel.modelName} Model)`" />
        <selectable-table-columns :columns="foreignTableColumns" full-width filterable :disabled="disabled" v-model="foreignDisplayColumn">
          <template slot-scope="{ entity }">
            <el-option :key="entity.id"
                       :label="entity.name"
                       :value="entity.id" />
          </template>
        </selectable-table-columns>
      </column>
      <column push10 size="6" v-if="!hideValueColumn">
        <form-input-title :centered="false" small :title="`Value Column (${foreignModel.modelName} Model)`" />
        <simple-select full-width disabled
                       :entities="foreignTableColumns"
                       v-model="foreignValueColumn">
          <template slot-scope="{ entity }">
            <el-option :key="entity.id"
                       :label="entity.name"
                       :value="entity.id" />
          </template>
        </simple-select>
      </column>
    </row>
  </div>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import SimpleSelect from '@/components/Select/SimpleSelect';
import SelectableTableColumns from '@/components/Select/SelectableTableColumns';

export default {
  name: 'OrchidRelation',
  props: {
    screen: {
      type: String,
      default: 'create',
    },
    tableName: String,
    tableId: [String, Number],
    model: {},
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
    SelectableTableColumns,
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
      valueColumnId: persisted.valueColumnId || null,
    };
  },
  computed: {
    broadcastable() {
      return {
        screen: this.screen,
        columnId: this.columnId,
        localModelId: this.model.id,
        foreignModelId: this.foreignModel.id,
        valueColumnId: this.foreignValueColumn,
        displayColumnId: this.displayColumnId,
      };
    },
    relation() {
      return this.relations.find((r) => r.localTable === this.tableId && r.localColumn === this.column.id);
    },
    foreignValueColumn: {
      get() {
        return this.foreignTableColumns.find((c) => c.id === this.relation.foreignColumn).id;
      },
    },
    foreignDisplayColumn: {
      get() {
        return this.displayColumnId || this.foreignTableColumns.find((c) => c.id === this.relation.foreignColumn).id;
      },
      set(value) {
        this.displayColumnId = value;
      },
    },
    localTableColumns() {
      if (!this.relation) {
        return [];
      }

      const model = this.blueprints.find((s) => s.id === this.relation.localTable) || {};
      return model.columns || [];
    },
    foreignTableColumns() {
      if (!this.relation) {
        return [];
      }

      return this.foreignModel.columns || [];
    },
    foreignModel() {
      if (!this.relation) {
        return {};
      }

      return this.blueprints.find((s) => s.id === this.relation.foreignTable) || {};
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
