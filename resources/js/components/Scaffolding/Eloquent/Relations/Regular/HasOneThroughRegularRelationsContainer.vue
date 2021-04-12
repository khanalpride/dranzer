<template>
  <scaffolding-component-container
    :heading="`Has One Through Relations (${relations.length})`"
    :loading="loading || fetchingMutations">
    <row>
      <column :size="hasSelection ? 12 : 8" :offset="hasSelection ? 0 : 2">
        <pg-input v-model="query" placeholder="Search for possible has-one-through relations..."/>
      </column>
      <column :size="hasSelection ? 12 : 8" :offset="hasSelection ? 0 : 2" push5>
        <p class="small text-info hint-text">e.g. Mechanic accesses Owner</p>
      </column>
    </row>
    <row v-if="groups.length">
      <column :size="hasSelection ? 12 : 8" :offset="hasSelection ? 0 : 2" push5>
        <p class="small text-primary">
          Showing <strong>{{ filteredGroups.length * 2 }}</strong> / <strong>{{ groups.length * 2 }}</strong> available
          relations<span v-if="relations.length"> (<strong>{{ relations.length }}</strong> enabled)</span>
        </p>
      </column>
      <column :push5="index === 0"
              :key="group.id"
              :size="hasSelection ? 12 : 8"
              :offset="hasSelection ? 0 : 2"
              v-for="(group, index) in filteredGroups">
        <row>
          <column v-if="index > 0">
            <separator/>
          </column>
          <column :key="rel.source.id + rel.related.id + rel.type" v-for="rel in group.relations">
            <pg-check-box no-margin
                          v-model="rel.enabled"
                          :disabled="processingToggleAll"
                          @change="onGroupedRelationToggled($event, rel, group)">
              <template slot="label">
                <strong>{{ rel.source.name }}</strong> <span class="text-complete bold">accesses</span> <strong>{{
                  rel.related.name
                }} <span class="text-primary">through the intermediate table</span></strong>
              </template>
            </pg-check-box>
          </column>
          <column push10 v-if="hasSelectionForGroup(group)">
            <row>
              <column>
                <p class="text-info hint-text no-margin">Intermediate Table Name</p>
                <pg-input v-model="group.intermediateTable"
                          class="m-t-5"
                          validate
                          :validation-result="isIntermediateTableNameValid(group)"
                          @input="persist(group)" />
              </column>
              <column>
                <pg-check-box no-margin class="p-t-5" v-model="group.showColumns" label="Show Columns" />
              </column>
              <column push10 v-if="group.showColumns">
                <template v-for="column in group.columns">
                  <table-column :key="column.id"
                                :column="column"
                                :disabled="column.disabled"
                                :options="{}"
                                disable-cloning
                                hide-type-aliases
                                disable-auto-incrementing
                                force-lowercase
                                @new="addCustomColumn({}, group)"
                                @delete="deleteCustomColumn(column, group)"
                                @name-updated="onColumnNameUpdated($event, column, group)"
                                @type-updated="onColumnTypeUpdated($event, column, group)"
                                @length-updated="onColumnLengthUpdated($event, column, group)"
                                @attr-updated="onColumnAttrUpdated($event, column, group)" />
                </template>
              </column>
              <column push10 v-if="group.showColumns">
                <button class="btn btn-primary" :disabled="group.columns.length > 3" @click="addCustomColumn({}, group)">
                  <i class="fa fa-plus"></i>
                </button>
              </column>
            </row>
          </column>
        </row>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import PgInput from '@/components/Forms/PgInput';
import Separator from '@/components/Layout/Separator';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import TableColumn from '@/components/Scaffolding/Database/Schema/Components/TableDefinition/TableColumn';
import ValidationHelpers from '@/helpers/validation_helpers';

export default {
  name: 'HasOneThroughRegularRelationsContainer',
  mixins: [asyncImports, mutations],
  props: {
    models: Array,
  },
  components: {
    TableColumn,
    PgCheckBox,
    Separator,
    PgInput,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  data() {
    return {
      loading: false,

      processingToggleAll: false,

      query: '',

      relations: [],

      groups: [],

      customColumns: [],
    };
  },
  computed: {
    filteredGroups() {
      const query = this.query.trim().toLowerCase();

      if (query === '') {
        return this.groups;
      }

      return this.groups.filter((g) => g.relations.find((rel) => {
        const normalCase = `${rel.source.name} accesses ${rel.related.name}`.toLowerCase();
        const inverseCase = `${rel.related.name} accesses ${rel.source.name}`.toLowerCase();

        try {
          return (new RegExp(query).test(normalCase)) || (new RegExp(query).test(inverseCase));
        } catch (e) {
          return null;
        }
      }));
    },

    selectionCount() {
      return this.groups.map((g) => g.relations).flat().filter((r) => r.enabled).length;
    },

    hasSelection() {
      return this.selectionCount > 0;
    },
  },
  async created() {
    this.loading = true;
    await this.syncRelations();
    this.groups = this.getGroups();
    this.loading = false;
  },
  methods: {
    async syncRelations() {
      const { data } = await this.mutation({
        path: 'eloquent/relations/regular/has-one-through',
        like: true,
        refresh: true,
      });

      this.relations = data.value ? data.value.map((v) => v.value) : this.relations;
    },

    hasSelectionForGroup(group) {
      return group.relations.flat().find((r) => r.enabled);
    },

    addCustomColumn(column, group) {
      if (group.columns.length > 3) {
        return;
      }

      group.columns.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        groupId: group.id,
        name: column.name || '',
        type: column.type || 'string',
        attributes: column.attributes || {
          ai: false,
          us: false,
          n: false,
          u: false,
          f: false,
          ug: false,
          h: false,
          length: null,
        },
        disabled: column.disabled !== undefined ? column.disabled : false,
      });
    },

    deleteCustomColumn(column, group) {
      const columnIndex = group.columns.findIndex((c) => c.id === column.id);
      if (columnIndex > -1) {
        group.columns.splice(columnIndex, 1);
        this.$nextTick(() => {
          this.persist(group);
        });
      }
    },

    isIntermediateTableNameValid(group) {
      return { tipPlacement: 'left', ...ValidationHelpers.isValidTableName(group.intermediateTable) };
    },

    getGroups() {
      const { models } = this;

      const groups = [];

      models.forEach((m) => {
        const modelId = m.id;
        const modelName = m.modelName.trim();
        const otherModels = models.filter((mod) => mod.id !== m.id);
        otherModels.forEach((otherModel) => {
          const otherModelColumn = otherModel.columns.find((c) => c.attributes.ai && c.attributes.us);
          if (otherModelColumn) {
            const group = groups.map((g) => g.relations).flat().find(
              (r) => (r.source.id === modelId && r.related.id === otherModel.id)
                || (r.related.id === modelId && r.source.id === otherModel.id),
            );

            if (group) {
              return false;
            }

            const firstCaseRel = this.relations.find(
              (r) => (r.source.id === modelId && r.related.id === otherModel.id),
            );

            const secondCaseRel = this.relations.find(
              (r) => (r.source.id === otherModel.id && r.related.id === modelId),
            );

            const persisted = firstCaseRel || secondCaseRel || {};

            const persistedColumns = persisted.columns || [];

            const columns = persistedColumns.length ? [] : [{
              id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
              name: 'id',
              type: 'bigIncrements',
              attributes: {
                ai: true,
                us: true,
                n: false,
                u: false,
                f: false,
                ug: false,
                h: false,
                length: null,
              },
              disabled: true,
            }, {
              id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
              name: `${this.str.snakeCase(modelName).toLowerCase()}_id`,
              type: 'unsignedBigInteger',
              attributes: {
                ai: false,
                us: true,
                n: false,
                u: false,
                f: false,
                ug: false,
                h: false,
                length: null,
              },
              disabled: true,
            }];

            const finalColumns = columns.concat(persistedColumns);

            groups.push({
              id: persisted.id || Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
              showColumns: !persisted,
              intermediateTable: persisted.intermediateTable || '',
              columns: finalColumns,
              relations: [
                {
                  id: firstCaseRel !== undefined ? firstCaseRel.id : null,
                  enabled: firstCaseRel !== undefined,
                  source: {
                    id: modelId,
                    name: modelName,
                  },
                  related: {
                    id: otherModel.id,
                    name: otherModel.modelName,
                  },
                }, {
                  id: secondCaseRel !== undefined ? secondCaseRel.id : null,
                  enabled: secondCaseRel !== undefined || false,
                  source: {
                    id: otherModel.id,
                    name: otherModel.modelName,
                  },
                  related: {
                    id: modelId,
                    name: modelName,
                  },
                },
              ],
            });
          }

          return true;
        });
      });

      return groups;
    },

    addRelation(relation) {
      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);

      this.relations.push({
        id,
        type: relation.type,
        inverse: relation.inverse,
        source: relation.source,
        related: relation.related,
      });
    },

    persist(group) {
      const relation = group.relations.flat().find((r) => r.enabled);

      if (!relation) {
        return;
      }

      this.$nextTick(() => {
        const sourceModel = relation.source;
        const relatedModel = relation.related;

        const name = 'Has One Through Relation';
        const path = `eloquent/relations/regular/has-one-through/${sourceModel.id}/${relatedModel.id}`;

        const payload = {
          name,
          path,
          value: {
            id: group.id,
            source: sourceModel,
            related: relatedModel,
            intermediateTable: group.intermediateTable,
            columns: group.columns || [],
            type: 'hasOneThrough',
          },
        };

        this.mutate(payload);
      });
    },

    onGroupedRelationToggled(checked, relation, group) {
      if (checked) {
        const enabledRelation = group.relations.flat().find((r) => r.enabled && r.source.id !== relation.source.id);
        if (enabledRelation) {
          enabledRelation.enabled = false;
        }

        const { columns } = group;
        const sourceColumn = columns.find((c) => c.type === 'unsignedBigInteger' && c.disabled);

        if (sourceColumn) {
          sourceColumn.name = `${this.str.snakeCase(relation.source.name).toLowerCase()}_id`;
        }

        const firstCaseRel = this.relations.find(
          (r) => r.source.id === relation.source.id && r.related.id === relation.related.id,
        );

        const secondCaseRel = this.relations.find(
          (r) => r.source.id === relation.related.id && r.related.id === relation.source.id,
        );

        if (secondCaseRel) {
          this.deleteMutation(`eloquent/relations/regular/has-one-through/${relation.related.id}/${relation.source.id}`, {
            then: () => {
              const rIndex = this.relations.findIndex((r) => r.id === secondCaseRel.id);
              if (rIndex > -1) {
                this.relations.splice(rIndex, 1);
              }
            },
          });
        }

        if (!firstCaseRel) {
          this.relations.push(relation);
          this.persist(group);
        }
      } else {
        const rIndex = this.relations.findIndex((r) => r.id === relation.id);
        if (rIndex > -1) {
          this.deleteMutation(`eloquent/relations/regular/has-one-through/${relation.source.id}/${relation.related.id}`, {
            then: () => {
              this.relations.splice(rIndex, 1);
              this.deleteMutation(`database/schemas/S${group.id}`);
            },
          });
        }
      }
    },

    onColumnNameUpdated(update, column, group) {
      column.name = update.newName;
      this.$nextTick(() => {
        this.persist(group);
      });
    },

    onColumnTypeUpdated(newType, column, group) {
      column.type = newType;
      this.$nextTick(() => {
        this.persist(group);
      });
    },

    onColumnLengthUpdated(updatedLength, column, group) {
      column.attributes.length = updatedLength;
      this.$nextTick(() => {
        this.persist(group);
      });
    },

    onColumnAttrUpdated(e, column, group) {
      const { checked, attr } = e;
      column.attributes[attr] = checked;

      this.$nextTick(() => {
        this.persist(group);
      });
    },
  },
};
</script>

<style scoped></style>
