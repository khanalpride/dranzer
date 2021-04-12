<template>
  <div>
    <table-definition-options :columns="columns"
                              :options="options"
                              :blueprint-count="blueprints.length"
                              :user-blueprint="userBlueprint"
                              :can-add-user-id-column="userBlueprint && userBlueprint.id !== blueprintId"
                              :is-user-table="isUserTable"
                              @column-toggled="onColumnToggled($event)"
                              @option-toggled="onOptionToggled($event)" />
    <separator />
    <content-container col-size="10" col-offset="1">
      <pg-input v-model="blueprint"
                placeholder="Type in the blueprint..."
                @keyup.enter.native="$emit('new-from-bp', blueprint)" />
    </content-container>
    <content-container push10 col-size="10" col-offset="1">
      <module-entity-container draggable
                               disable-add
                               :check-move-callback="canColumnBeReordered"
                               :entities="columns"
                               @drag-end="onColumnDragged"
                               v-if="!loading">
        <template slot-scope="column">
          <row>
            <column>
              <keep-alive>
                <table-column :ref="`column-${column.id}`"
                              :key="column.id"
                              :column="column"
                              :auto-incrementing-column="autoIncrementingColumn"
                              :options="options"
                              :disabled="isColumnDisabled(column)"
                              :locked="mutatingColumn"
                              @new="$emit('new', column)"
                              @clone="$emit('clone', column)"
                              @delete="$emit('delete', column)"
                              @name-updated="broadcastUpdate($event, 'name', column)"
                              @type-updated="broadcastUpdate($event, 'type', column)"
                              @length-updated="broadcastUpdate($event, 'length', column)"
                              @attr-updated="broadcastUpdate($event, 'attr', column)" />
              </keep-alive>
            </column>
          </row>
        </template>
      </module-entity-container>
    </content-container>
  </div>
</template>

<script>
import TableDefinitionOptions
  from '@/components/Scaffolding/Database/Schema/Components/TableDefinition/TableDefinitionOptions';
import Separator from '@/components/Layout/Separator';
import TableColumn from '@/components/Scaffolding/Database/Schema/Components/TableDefinition/TableColumn';
import ModuleEntityContainer from '@/components/Scaffolding/Containers/ModuleEntityContainer';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ContentContainer from '@/components/Content/ContentContainer';
import PgInput from '@/components/Forms/PgInput';

export default {
  name: 'TableDefinition',
  props: {
    blueprintId: [String, Number],
    blueprints: Array,
    cols: Array,
    userBlueprint: {},
    options: {},
    mutatingColumn: Boolean,
    isUserTable: Boolean,
  },
  components: {
    PgInput,
    ContentContainer,
    Column,
    Row,
    ModuleEntityContainer,
    TableColumn,
    Separator,
    TableDefinitionOptions,
  },
  data() {
    return {
      loading: false,

      blueprint: '',

      columns: [],
    };
  },
  computed: {
    autoIncrementingColumn() {
      return this.columns.find((c) => c.attributes.ai);
    },
  },
  watch: {
    cols: {
      handler(v) {
        this.columns = v;
      },
    },
  },
  async created() {
    // eslint-disable-next-line no-restricted-syntax
    for (const col of this.cols) {
      // eslint-disable-next-line no-await-in-loop
      await this.promises.sleep(1);
      this.columns.push(col);
    }
  },
  methods: {
    focusColumnNameInput(column) {
      this.$nextTick(() => {
        if (this.$refs[`column-${column.id}`]) {
          this.$refs[`column-${column.id}`].focusColumnNameInput();
        }
      });
    },

    broadcastUpdate(update, type, column) {
      this.$emit('updated', { column, type, update });
    },

    isColumnDisabled(column) {
      return column.disabled || (this.options.createModel
        && this.options.softDelete
        && column.name
        && column.name.trim() === 'deleted_at') === true;
    },

    canColumnBeReordered(event) {
      const { element } = event.draggedContext;
      const { futureIndex } = event.draggedContext;

      if (element.disabled) {
        return false;
      }

      const columnName = element.name;
      const columnAttributes = element.attributes;

      if (columnAttributes.ai && columnAttributes.us) {
        return false;
      }

      if (columnName === 'created_at' || columnName === 'updated_at' || columnName === 'deleted_at') {
        return false;
      }

      let idIndex = -1;
      let cAtIndex = -1;
      let uAtIndex = -1;
      let dAtIndex = -1;

      let index = 0;

      const foreignKeyIndexes = [];

      this.columns.forEach((column) => {
        const { name } = column;
        const attrs = column.attributes;

        if (name.substr(name.length - 3) === '_id') {
          foreignKeyIndexes.push(index);
        }

        if (name === 'id') {
          if (attrs.ai && attrs.us) {
            idIndex = index;
          }
        }

        if (name === 'created_at') {
          cAtIndex = index;
        }

        if (name === 'updated_at') {
          uAtIndex = index;
        }

        if (name === 'deleted_at') {
          dAtIndex = index;
        }

        index += 1;
      });

      if (foreignKeyIndexes.includes(futureIndex)) {
        return false;
      }

      return (
        futureIndex !== idIndex
        && futureIndex !== cAtIndex
        && futureIndex !== uAtIndex
        && futureIndex !== dAtIndex
      );
    },

    onColumnDragged(reOrderedColumns) {
      this.$emit('order-changed', reOrderedColumns);
    },

    onColumnToggled(e) {
      this.$emit('column-toggled', e);
    },

    onOptionToggled(e) {
      this.$emit('option-toggled', e);
    },
  },
};
</script>

<!--suppress CssUnusedSymbol -->
<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.08s;
}

.list-enter, .list-leave-to /* .list-leave-active below version 2.1.8 */ {
  opacity: 0;
  transform: translateY(20px);
}
</style>
