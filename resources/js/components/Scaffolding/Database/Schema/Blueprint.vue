<template>
  <div>
    <initializing-progress-container :initializing="loading"/>
    <content-container :loading="loading">
      <blueprint-database-switcher/>
      <content-container>
        <blueprint-identity ref="blueprintIdentity"
                         :options="options"
                         :persisted-model-name="blueprint.modelName"
                         :persisted-table-name="blueprint.tableName"
                         :cannot-edit="!canEditModelName || !canEditTableName"
                         @capitalize-toggled="onModelOptionToggled('capitalize', $event)"
                         @model-name-updated="onModelNameUpdated"
                         @table-name-updated="onTableNameUpdated"/>
      </content-container>
      <content-container>
        <separator />
        <blueprint-options :options="options"
                        :mandatory-model="mandatoryModel"
                        @create-model-toggled="onCreateModelOptionToggled"
                        @soft-delete-toggled="onSoftDeleteOptionToggled"
                        @unguarded-toggled="onUnguardedOptionToggled" />
      </content-container>
      <basic-content-section :heading="`Definition (${blueprint.columns.length} Columns)`" prepend-separator>
        <table-definition ref="tableDefinition"
                          :cols="blueprint.columns"
                          :blueprints="blueprints"
                          :options="options"
                          :blueprint-id="blueprintId"
                          :user-blueprint="userBlueprint"
                          :mutating-column="mutatingColumn"
                          :is-user-table="blueprint.modelName === 'User'"
                          @order-changed="blueprint.columns = $event"
                          @column-toggled="onColumnToggled"
                          @option-toggled="onTableDefinitionOptionToggled"
                          @clone="onCloneColumn"
                          @new="onCreateNewColumn"
                          @new-from-bp="onCreateFromBlueprint"
                          @delete="onDeleteColumn"
                          @updated="onColumnUpdated" />
        <content-container push5 col-size="10" col-offset="1">
          <simple-button color-class="primary" @click="addColumn()">
            <i class="fa fa-plus"></i>
          </simple-button>
        </content-container>
      </basic-content-section>
      <basic-content-section :heading="`Foreign Keys (${foreignKeyCount})`" prepend-separator>
        <table-relations-manager ref="relationsManager"
                                  :options="options"
                                  :blueprint="blueprint"
                                  :blueprints="rawBlueprints"
                                  @count-changed="foreignKeyCount = $event" />
      </basic-content-section>
      <basic-content-section :heading="`Scopes (${scopeCount})`" prepend-separator>
        <eloquent-scope-manager :blueprint="blueprint" @count-changed="scopeCount = $event" />
      </basic-content-section>
      <basic-content-section heading="Factory and Seeder" prepend-separator>
        <factory-manager :blueprint="blueprint" />
      </basic-content-section>
    </content-container>
  </div>
</template>

<script>
import pluralize from 'pluralize';
import { snakeCase } from 'snake-case';

import mutations from '@/mixins/mutations';

import sharedMutations from '@/mixins/shared_mutations';

import ContentContainer from '@/components/Content/ContentContainer';
import BasicContentSection from '@/components/Content/BasicContentSection';
import BlueprintOptions from '@/components/Scaffolding/Database/Schema/Components/BlueprintOptions';
import BlueprintIdentity from '@/components/Scaffolding/Database/Schema/Components/BlueprintIdentity';
import TableDefinition from '@/components/Scaffolding/Database/Schema/Components/TableDefinition';
import BlueprintDatabaseSwitcher from '@/components/Scaffolding/Database/Schema/Components/BlueprintDatabaseSwitcher';
import InitializingProgressContainer from '@/components/Scaffolding/Containers/Progress/InitializingProgressContainer';
import entity from '@/mixins/entity';
import Separator from '@/components/Layout/Separator';
import TableRelationsManager
  from '@/components/Scaffolding/Database/Schema/Components/Relations/TableRelationsManager';
import FactoryManager from '@/components/Scaffolding/Database/Schema/Components/Factories/FactoryManager';
import columnTypes from '@/data/scaffolding/database/blueprint/column_types';
import EloquentScopeManager from '@/components/Scaffolding/Database/Schema/Components/Scopes/EloquentScopeManager';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';

export default {
  name: 'Blueprint',
  mixin: [mutations],
  props: {
    blueprints: Array,
    rawBlueprints: Array,
    blueprintId: [String, Number],
    persisted: {},
    canEditModelName: Boolean,
    canEditTableName: Boolean,
    mandatoryModel: Boolean,
  },
  components: {
    TableRelationsManager,
    SimpleButton,
    EloquentScopeManager,
    FactoryManager,
    Separator,
    ContentContainer,
    InitializingProgressContainer,
    TableDefinition,
    BlueprintDatabaseSwitcher,
    BlueprintOptions,
    BlueprintIdentity,
    BasicContentSection,
  },
  mixins: [mutations, sharedMutations, entity],
  data() {
    return {
      loading: false,
      ready: false,
      definitionLoaded: false,
      processingBatchInsertion: false,

      mutatingColumn: false,

      blueprint: {
        modelName: '',
        tableName: '',
        columns: [],
      },

      options: {
        createModel: true,
        softDelete: false,
        unguarded: false,
        forceLowercase: true,
        automaticForeignKeys: true,
        capitalize: true,
      },

      foreignKeyCount: 0,
      scopeCount: 0,
    };
  },
  computed: {
    userBlueprint() {
      return this.blueprints.find((s) => s.id.substr(0, 13) === 'UserBlueprint');
    },
  },
  watch: {
    blueprint: {
      handler(v) {
        if (!this.ready || !v) {
          return;
        }

        if (v.createModel) {
          delete v.createModel;
        }

        const blueprint = { ...v, columns: v.columns.filter((c) => c.name.trim() !== '') };

        this.broadcastSync();

        this.mutate({
          name: 'Blueprint',
          path: `database/blueprints/${this.blueprintId}`,
          value: blueprint,
        });
      },
      deep: true,
    },
    options: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({
          name: 'Blueprint Config',
          path: `config/database/blueprints/${this.blueprintId}`,
          value: v,
        });
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;

    const blueprint = this.syncEntity(this.persisted, this.blueprint);

    this.blueprint = blueprint;

    if (!blueprint.columns.length) {
      this.processingBatchInsertion = true;

      this.addColumn({
        name: 'id',
        type: 'bigIncrements',
        attr: { ai: true, us: true, h: false },
        atIndex: 0,
      });

      this.addColumn({ name: 'created_at', type: 'timestamp', attr: { n: true } });

      this.addColumn({ name: 'updated_at', type: 'timestamp', attr: { n: true } });

      this.processingBatchInsertion = false;
    }

    await this.syncOptions();

    this.loading = false;

    setTimeout(() => {
      this.ready = true;
    }, 1000);
  },
  methods: {
    async syncOptions() {
      const { data } = await this.mutation({ path: `config/database/blueprints/${this.blueprintId}` });
      this.options = this.getPersistedMutationValue(data) || this.options;
    },

    addColumn(data) {
      const def = data || {};

      if (!def.attr) {
        def.attr = {};
      }

      const column = {
        name: def.name || '',
        type: def.type || 'string',
        attributes: {
          ai: def.attr.ai || false,
          us: def.attr.us || false,
          n: def.attr.n || false,
          u: def.attr.u || false,
          f: def.attr.f || false,
          ug: def.attr.ug || false,
          h: def.attr.h || false,
          length: def.attr.length || null,
        },
      };

      const obj = {
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        ...column,
      };

      if (!this.processingBatchInsertion) {
        if (def.atIndex > -1) {
          this.blueprint.columns.splice(def.atIndex, 0, obj);
        } else {
          const { columns } = this.blueprint;

          const createdAtColumnIndex = columns.findIndex(
            (c) => c.name === 'created_at',
          );
          const updatedAtColumnIndex = columns.findIndex(
            (c) => c.name === 'updated_at',
          );
          const deletedAtColumnIndex = columns.findIndex(
            (c) => c.name === 'deleted_at',
          );

          let insertionIndex = columns.length;

          if (deletedAtColumnIndex) {
            insertionIndex = deletedAtColumnIndex;
          }

          if (updatedAtColumnIndex) {
            insertionIndex = updatedAtColumnIndex;
          }

          if (createdAtColumnIndex) {
            insertionIndex = createdAtColumnIndex;
          }

          this.blueprint.columns.splice(insertionIndex, 0, obj);
        }
      } else {
        this.blueprint.columns.push(obj);
      }

      this.$nextTick(() => {
        if (this.$refs.tableDefinition && def.focus !== false && !this.processingBatchInsertion) {
          this.$refs.tableDefinition.focusColumnNameInput(obj);
        }
      });

      return obj;
    },

    getColumnIndex(columnId) {
      return this.blueprint.columns.findIndex((c) => c.id === columnId);
    },

    sortColumns() {
      this.blueprint.columns.sort((a, b) => {
        if (
          a.name === 'id'
          && (a.type.toLowerCase().indexOf('increment') > -1
          || a.type.toLowerCase().indexOf('integer') > -1)
          && a.attributes.ai === true
          && a.attributes.us === true
        ) {
          return -1;
        }

        if (
          b.name === 'id'
          && (b.type.toLowerCase().indexOf('increment') > -1
          || b.type.toLowerCase().indexOf('integer') > -1)
          && b.attributes.ai === true
          && b.attributes.us === true
        ) {
          return 1;
        }

        const createdAt = b.name === 'created_at';
        const updatedAt = b.name === 'updated_at';
        const deletedAt = b.name === 'deleted_at';

        // eslint-disable-next-line no-underscore-dangle
        const _updatedAt = a.name === 'updated_at';
        // eslint-disable-next-line no-underscore-dangle
        const _deletedAt = a.name === 'deleted_at';

        if (createdAt) {
          return _updatedAt || _deletedAt ? 1 : -1;
        }

        if (updatedAt) {
          return _deletedAt ? 1 : -1;
        }

        if (deletedAt) {
          return -1;
        }

        return 0;
      });
    },

    broadcastSync() {
      this.$emit('sync', this.blueprint);
    },

    // eslint-disable-next-line consistent-return
    toggleColumn(columnIndex, newColumn, checked) {
      if (!checked && columnIndex > -1) {
        this.blueprint.columns.splice(columnIndex, 1);
        return null;
      }

      if (checked && columnIndex < 0) {
        return this.addColumn(newColumn);
      }
    },

    canAutoIncrement(column) {
      return column.type.toLowerCase().indexOf('integer') > -1
        || column.type.toLowerCase().indexOf('increment') > -1;
    },

    addForeignKey(localColumn) {
      if (!localColumn.id) {
        return;
      }

      const localColumnName = localColumn.name;
      const localColumnIndex = this.getColumnIndex(localColumn.id);

      if (localColumnName.indexOf('_') < 0) {
        return;
      }

      const segments = localColumnName.split('_');
      const tableName = segments.slice(0, segments.length - 1).join('_');
      const columnName = segments.slice(segments.length - 1).join('');

      if (tableName && columnName && columnName.trim() === 'id') {
        const pluralizedTableName = pluralize(tableName.toLowerCase());

        const foreignSchema = this.blueprints.find((s) => s.tableName.toLowerCase() === pluralizedTableName);

        if (foreignSchema && this.$refs.relationsManager) {
          if (foreignSchema.tableName === this.blueprint.tableName) {
            return;
          }

          const foreignColumn = foreignSchema.columns.find((c) => c.attributes.us);

          if (foreignColumn) {
            this.blueprint.columns[localColumnIndex].type = 'unsignedBigInteger';

            this.blueprint.columns[localColumnIndex].attributes.us = true;

            const relation = {
              localColumn: localColumn.id,
              foreignTable: foreignSchema.id,
              foreignColumn: foreignColumn.id,
            };

            this.$refs.relationsManager.addRelation(relation);

            this.mutatingColumn = true;

            this.$nextTick(() => {
              this.mutatingColumn = false;
            });
          }
        }
      }
    },

    async onSchemaColumnNameUpdated(column, update) {
      const columnIndex = this.getColumnIndex(column.id);
      if (columnIndex > -1) {
        const { newName } = update;

        let newColName = newName;

        const alias = newColName.indexOf(':') > -1 ? this.rgx.getFirstMatch(/(?<=:)\w+/, newColName) : null;

        const columnType = columnTypes.find((t) => t.alias.toLowerCase() === alias);

        this.mutatingColumn = true;
        if (columnType) {
          let len = null;

          if (columnType.acceptsLen) {
            len = newColName.indexOf('(') > -1 && newColName.indexOf(')') > -1
              ? this.rgx.getFirstMatch(/(?<=\()\d+(?=\))/, newColName)
              : null;
          }

          if (columnType.acceptsLen && Number.isInteger(Number.parseInt(len, 10))) {
            column.type = columnType.name;
            this.blueprint.columns[columnIndex].type = columnType.name;
            this.blueprint.columns[columnIndex].attributes.length = Number(len);
            newColName = newColName.substr(0, newColName.lastIndexOf(':'));
            // this.addColumn({ atIndex: this.getColumnIndex(column.id) + 1 });
          }

          if (!columnType.acceptsLen || newColName.endsWith('!')) {
            column.type = columnType.name;
            this.blueprint.columns[columnIndex].type = columnType ? columnType.name : column.type;
            newColName = newColName.substr(0, newColName.lastIndexOf(':'));
            // this.addColumn({ atIndex: this.getColumnIndex(column.id) + 1 });
          }
        }

        column.name = newColName;

        this.blueprint.columns[columnIndex].name = newColName;

        this.$nextTick(() => {
          this.mutatingColumn = false;
          this.addForeignKey(column);
        });
      }
    },

    onSchemaColumnTypeUpdated(column, updatedType) {
      const columnIndex = this.getColumnIndex(column.id);
      if (columnIndex > -1) {
        this.blueprint.columns[columnIndex].type = updatedType;

        if (updatedType.indexOf('integer') < 0 || updatedType.indexOf('increment') < 0) {
          this.blueprint.columns[columnIndex].attributes.us = false;
          this.blueprint.columns[columnIndex].attributes.ai = false;
        }
      }
    },

    onSchemaColumnLengthUpdated(column, updatedLength) {
      const columnIndex = this.getColumnIndex(column.id);
      if (columnIndex > -1) {
        this.blueprint.columns[columnIndex].attributes.length = updatedLength;
      }
    },

    onSchemaColumnAttrUpdated(column, update) {
      const columnIndex = this.getColumnIndex(column.id);
      if (columnIndex > -1) {
        const { checked, attr } = update;

        this.blueprint.columns[columnIndex].attributes[attr] = checked;

        const columnAttributes = this.blueprint.columns[columnIndex].attributes;

        // Toggle Fillable and Unguarded Attributes
        if (attr === 'f' && columnAttributes.ug) {
          this.blueprint.columns[columnIndex].attributes.ug = false;
        }

        if (attr === 'ug' && columnAttributes.f) {
          this.blueprint.columns[columnIndex].attributes.f = false;
        }

        // If the auto-increment was enabled for this column,
        // disable it for the previous auto-incrementing column.
        if (columnAttributes.ai) {
          this.blueprint.columns.forEach((c) => {
            if (c.id !== column.id && c.attributes.ai) {
              const autoIncrementingColumnIndex = this.getColumnIndex(c.id);
              this.blueprint.columns[autoIncrementingColumnIndex].attributes.ai = false;
            }
          });
        }

        this.sortColumns();
      }
    },

    onColumnUpdated(e) {
      const { column, type, update } = e;
      if (type === 'name') {
        this.onSchemaColumnNameUpdated(column, update);
      }
      if (type === 'type') {
        this.onSchemaColumnTypeUpdated(column, update);
      }
      if (type === 'length') {
        this.onSchemaColumnLengthUpdated(column, update);
      }
      if (type === 'attr') {
        this.onSchemaColumnAttrUpdated(column, update);
      }
    },

    onColumnToggled(e) {
      const { columnName, checked } = e;
      const { columns } = this.blueprint;

      const idColumnIndex = columns.findIndex(
        (c) => c.name === 'id',
      );

      const createdAtColumnIndex = columns.findIndex(
        (c) => c.name === 'created_at',
      );

      const updatedAtColumnIndex = columns.findIndex(
        (c) => c.name === 'updated_at',
      );

      const deletedAtColumnIndex = columns.findIndex(
        (c) => c.name === 'deleted_at',
      );

      if (columnName === 'id') {
        this.toggleColumn(idColumnIndex, {
          name: columnName, type: 'bigIncrements', attr: { ai: true, us: true }, atIndex: 0,
        }, checked);
      }

      if (columnName === 'uuid') {
        const uuidColumnIndex = columns.findIndex(
          (c) => c.name === columnName,
        );
        this.toggleColumn(uuidColumnIndex, {
          name: columnName, type: 'uuid', attr: { u: true }, atIndex: idColumnIndex > -1 ? 1 : 0,
        }, checked);
      }

      if (columnName === 'user_id') {
        let insertionIndex = columns.length;

        if (deletedAtColumnIndex > -1) {
          insertionIndex = deletedAtColumnIndex;
        }

        if (updatedAtColumnIndex > -1) {
          insertionIndex = updatedAtColumnIndex;
        }

        if (createdAtColumnIndex > -1) {
          insertionIndex = createdAtColumnIndex;
        }

        const userIdColumnIndex = columns.findIndex(
          (c) => c.name === columnName,
        );

        const userIdColumn = {
          name: columnName,
          type: 'unsignedBigInteger',
          attr: { us: true },
          atIndex: insertionIndex,
        };

        const addedColumn = this.toggleColumn(userIdColumnIndex, userIdColumn, checked);

        if (checked && addedColumn) {
          this.$nextTick(() => {
            this.addForeignKey(addedColumn);
          });
        }
      }

      // Timestamp Columns
      if (columnName.indexOf('_at') > -1) {
        let columnIndex = -1;
        let insertionIndex = columns.length;

        if (columnName === 'created_at') {
          if (updatedAtColumnIndex > -1) {
            insertionIndex = updatedAtColumnIndex;
          }

          if (updatedAtColumnIndex < 0 && deletedAtColumnIndex > -1) {
            insertionIndex = deletedAtColumnIndex;
          }

          columnIndex = createdAtColumnIndex;
        }

        if (columnName === 'updated_at') {
          insertionIndex = deletedAtColumnIndex > -1 ? deletedAtColumnIndex : columns.length;
          columnIndex = updatedAtColumnIndex;
        }

        if (columnName === 'deleted_at') {
          insertionIndex = columns.length;
          columnIndex = deletedAtColumnIndex;
        }

        this.toggleColumn(columnIndex,
          {
            name: columnName,
            type: 'timestamp',
            attr: { n: true },
            atIndex: insertionIndex,
          },
          checked);
      }
    },

    onTableDefinitionOptionToggled(e) {
      const { name, checked } = e;
      this.options[name] = checked;
    },

    async onModelNameUpdated(e) {
      this.blueprint.modelName = e.oldName;
      await this.promises.sleep(1);
      this.blueprint.modelName = e.newName;
      const newTableName = this.$refs.blueprintIdentity.getNewTableName(e.newName);
      this.blueprint.tableName = snakeCase(pluralize(newTableName));

      this.broadcastSync();
    },

    async onTableNameUpdated(e) {
      this.blueprint.tableName = e.newName;
      this.broadcastSync();
    },

    onModelOptionToggled(optionName, checked) {
      this.options[optionName] = checked;
      this.$refs.blueprintIdentity.focusModelNameInput();
    },

    onCreateModelOptionToggled(checked) {
      this.options.createModel = checked;

      if (checked && this.options.softDelete) {
        const deletedAtColumnIndex = this.blueprint.columns.findIndex((c) => c.name === 'deleted_at');
        if (deletedAtColumnIndex > -1) {
          this.blueprint.columns[deletedAtColumnIndex].type = 'timestamp';
          this.blueprint.columns[deletedAtColumnIndex].attributes = {
            ai: false, us: false, n: true, u: true, f: false, ug: false, h: true,
          };
        }
      }

      this.$emit('create-model-toggled', checked);
    },

    onSoftDeleteOptionToggled(checked) {
      this.options.softDelete = checked;
      const deletedAtColumnIndex = this.blueprint.columns.findIndex((c) => c.name === 'deleted_at');
      if (checked) {
        if (deletedAtColumnIndex < 0) {
          this.addColumn({
            name: 'deleted_at',
            type: 'timestamp',
            attr: { n: true, h: true },
            atIndex: this.blueprint.columns.length,
          });
        } else {
          this.blueprint.columns[deletedAtColumnIndex].type = 'timestamp';
          this.blueprint.columns[deletedAtColumnIndex].attributes.n = true;
        }
      }
    },

    onUnguardedOptionToggled(checked) {
      this.options.unguarded = checked;
      if (checked) {
        this.blueprint.columns.forEach((c) => c.attributes.ug = true);
      } else {
        this.blueprint.columns.forEach((c) => c.attributes.ug = false);
      }
    },

    onCreateNewColumn(requestingColumn) {
      if (!requestingColumn.name || requestingColumn.name.trim() === '') {
        return;
      }

      const addedColumn = this.addColumn({ atIndex: this.getColumnIndex(requestingColumn.id) + 1 });

      this.sortColumns();

      this.$nextTick(() => {
        this.$refs.tableDefinition.focusColumnNameInput(addedColumn);
      });
    },

    onCreateFromBlueprint(blueprint) {
      const colTypes = columnTypes;

      const columns = blueprint.split(',');

      const newColumns = [];

      const types = [];

      let lastType = null;

      let lastAttrs = null;

      columns.forEach((column) => {
        if (!column || column.trim() === '') {
          return false;
        }

        const col = this.blueprint.columns.find((c) => c.name && c.name.trim() === column.trim());

        if (col) {
          return false;
        }

        const attrs = {
          ai: false, us: false, n: false, f: false, ug: false, h: false, length: null,
        };

        let columnData = null;

        const columnName = column.trim();

        const parts = column.split(':');

        let colReferencedIndex = null;

        let type = 'string';

        let params = null;

        const typeInfo = colTypes.find((c) => c.name === type || c.alias === type);

        const arg = parts.length <= 1 ? null : parts.slice(parts.length - 1).join('').trim();

        if (arg && arg !== '' && !Number.isInteger(Number.parseInt(arg, 10))) {
          const colName = columnName.substr(0, columnName.lastIndexOf(':'));
          type = this.rgx.getFirstMatch(/^(\$\d+|\w+)/, arg);
          if (type) {
            if (arg.indexOf('(') > -1 && arg.indexOf(')') > -1) {
              params = this.rgx.getFirstMatch(/(?<=\().*?(?=\))/, arg);
            }
            if (type.trim().startsWith('$')) {
              colReferencedIndex = this.rgx.getFirstMatch(/\d+/, type);
              if (colReferencedIndex && colReferencedIndex > 0 && colReferencedIndex <= types.length) {
                const colRef = types.find((t) => t.index === Number(colReferencedIndex));
                if (colRef) {
                  const referencedType = colTypes.find((c) => c.name === colRef.type || c.alias === colRef.type);
                  if (!referencedType) {
                    columnData = { name: colName, type: typeInfo ? typeInfo.name : type, attr: colRef.attrs };
                    lastType = typeInfo ? typeInfo.name : type;
                  } else {
                    columnData = { name: colName, type: referencedType.name, attr: colRef.attrs };
                    lastType = referencedType.name;
                  }

                  types.push({
                    index: types.length + 1,
                    type: lastType,
                    attrs: colRef.attrs,
                  });

                  lastAttrs = colRef.attrs;
                  // first_name:50,middle_name...,last_name...,mobile:15,email:$4,x,$5
                }
              }
            } else {
              const referencedType = colTypes.find((c) => c.name === type || c.alias === type);
              if (referencedType) {
                const modifiedAttrs = {};
                if (params && Number.isInteger(Number.parseInt(params, 10))) {
                  modifiedAttrs.length = Number(params);
                }
                const newColumnAttrs = { ...attrs, ...modifiedAttrs };
                columnData = { name: colName, type: referencedType.name, attr: newColumnAttrs };

                types.push({
                  index: types.length + 1,
                  type: referencedType.name,
                  attrs: newColumnAttrs,
                });

                lastType = referencedType.name;
                lastAttrs = newColumnAttrs;
              }
            }
          }
        }
        if (Number.isInteger(Number.parseInt(arg, 10))) {
          const colName = columnName.substr(0, columnName.lastIndexOf(':'));
          columnData = { name: colName, type: 'string', attr: attrs };

          columnData.attr.length = Number(arg);

          types.push({
            index: types.length + 1,
            type: 'string',
            attrs: columnData.attr,
          });

          lastType = 'string';
          lastAttrs = attrs;
        }

        if (columnName.endsWith('...') && lastAttrs) {
          const colName = columnName.substr(0, columnName.indexOf('.'));
          columnData = { name: colName, type: lastType, attr: lastAttrs };
          types.push({
            index: types.length + 1,
            type: lastType,
            attrs: lastAttrs,
          });
        } else if (parts.length === 1) {
          columnData = {
            name: columnName, type, attr: attrs, plain: true,
          };
          types.push({
            index: types.length + 1,
            type,
            attrs,
          });
        }

        if (columnData) {
          newColumns.push(columnData);
        }

        return true;
      });

      newColumns.forEach((c) => {
        const data = JSON.parse(JSON.stringify(c));
        if (data.plain === true) {
          delete data.plain;
          const addedColumn = this.addColumn(data);
          this.$nextTick(() => {
            this.addForeignKey(addedColumn);
          });
        } else {
          this.addColumn(data);
        }
      });
    },

    onCloneColumn(columnToClone) {
      const index = this.getColumnIndex(columnToClone.id);

      const clonedColumn = this.addColumn({ ...columnToClone, atIndex: index + 1 });

      this.sortColumns();

      this.$nextTick(() => {
        this.$refs.tableDefinition.focusColumnNameInput(clonedColumn);
      });
    },

    onDeleteColumn(column) {
      const columnIndex = this.getColumnIndex(column.id);
      if (columnIndex > -1) {
        this.blueprint.columns.splice(columnIndex, 1);
      }
    },
  },
};
</script>

<style scoped>
</style>
