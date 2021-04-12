<template>
  <div>
    <row v-if="!models.length">
      <column centered>
        <p class="text-info hint-text">
          No model statements can be generated at this time.
        </p>
      </column>
    </row>
    <row v-else>
      <column centered>
        <text-block info hinted>
          Eloquent related statements that can be added to the <strong>{{ method }}</strong> method.
        </text-block>
      </column>
      <column>
        <basic-content-section heading="Type Hint Models" prepend-separator>
          <row>
            <column :key="model.id" size="3" v-for="model in models">
              <pg-check-box no-margin
                            :value="isTypeHinted(model)"
                            :label="model.modelName"
                            @change="onTypeHintedModelStateChanged($event, model)" />
            </column>
          </row>
        </basic-content-section>
      </column>
      <column>
        <basic-content-section heading="Model Statements" prepend-separator>
          <row>
            <column>
              <tabs-manager :tabs="modelTabs"
                            :path="`config/controllers/tabs/stmts/models/active/${controllerId}/${method}`">
                <template :slot="tab.id" v-for="tab in modelTabs">
                  <row :key="tab.id">
                    <column>
                      <row>
                        <column>
                          <pg-check-box block
                                        no-margin
                                        :value="hasPaginateStmt(tab.model)"
                                        :label="`Paginate ${str.snakeCase(str.pluralize(tab.model.modelName))}`"
                                        @change="onPaginateModelChanged($event, tab.model)" />
                        </column>
                        <column size="9" class="m-l-30" v-if="hasPaginateStmt(tab.model) && getModelRelations(tab.model).length">
                          <row>
                            <column :key="relation.id" v-for="relation in getModelRelations(tab.model)">
                              <pg-check-box block
                                            no-margin
                                            :value="hasRelation(relation, tab.model, 'paginate')"
                                            @change="onWithRelationStateChanged($event, relation, tab.model,'paginate')">
                                <template slot="label">
                                  With
                                  <span class="text-complete bold">{{ relation.relationName }}</span>
                                  <span class="text-primary bold">
                                    <i class="fa fa-arrow-right small" />
                                    {{ relation.type }}
                                  </span>
                                </template>
                              </pg-check-box>
                            </column>
                          </row>
                        </column>
                      </row>
                    </column>
                    <column>
                      <row>
                        <column>
                          <pg-check-box block
                                        no-margin
                                        :value="hasGetAllStmt(tab.model)"
                                        :label="`Get all ${str.snakeCase(str.pluralize(tab.model.modelName))}`"
                                        @change="onGetAllModelsChanged($event, tab.model)" />
                        </column>
                        <column size="9" class="m-l-30" v-if="hasGetAllStmt(tab.model) && getModelRelations(tab.model).length">
                          <row>
                            <column :key="relation.id" v-for="relation in getModelRelations(tab.model)">
                              <pg-check-box block
                                            no-margin
                                            :value="hasRelation(relation, tab.model, 'getAll')"
                                            @change="onWithRelationStateChanged($event, relation, tab.model,'getAll')">
                                <template slot="label">
                                  With
                                  <span class="text-complete bold">{{ relation.relationName }}</span>
                                  <span class="text-primary bold">
                                    <i class="fa fa-arrow-right small" />
                                    {{ relation.type }}
                                  </span>
                                </template>
                              </pg-check-box>
                            </column>
                          </row>
                        </column>
                      </row>

                    </column>
                    <column>
                      <separator />
                    </column>
                    <column :key="column.id" size="3" v-for="column in stringColumns(tab.model)">
                      <pg-check-box no-margin
                                    :label="`Pluck ${column.name}`"
                                    :value="hasPluckStmt(tab.model, column)"
                                    @change="onPluckModelColumnChanged($event, tab.model, column)" />

                    </column>
                  </row>
                </template>
              </tabs-manager>
            </column>
          </row>
        </basic-content-section>
      </column>
    </row>
  </div>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import TabsManager from '@/components/Tabs/TabsManager';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import TextBlock from '@/components/Typography/Decorated/TextBlock';
import BasicContentSection from '@/components/Content/BasicContentSection';

export default {
  name: 'ControllerModelIntegration',
  components: {
    TextBlock,
    Separator,
    TabsManager,
    BasicContentSection,
    PgCheckBox,
    Row,
    Column,
  },
  props: {
    persisted: {},
    blueprints: Array,
    controllerId: [String, Number],
    method: String,
    routeModel: {},
    eloquentRelations: Array,
  },
  data() {
    return {
      typeHint: [],
      stmts: [],
    };
  },
  computed: {
    persistable() {
      return {
        typeHint: this.typeHint,
        stmts: this.stmts,
      };
    },

    models() {
      return this.blueprints.filter((s) => s.modelName && s.modelName.trim() !== '' && s.visible !== false);
    },

    modelTabs() {
      return this.models.map((m) => ({
        id: `M${m.id}`,
        label: m.modelName,
        model: m,
      }));
    },
  },
  watch: {
    persisted: {
      handler(v) {
        if (v) {
          this.typeHint = v.typeHint || [];
          this.stmts = v.stmts || [];
        }
      },
      immediate: true,
    },
    persistable: {
      handler(v) {
        this.$emit('stmts-changed', v);
      },
      deep: true,
    },
  },
  methods: {
    isTypeHinted(model) {
      return this.typeHint.includes(model.id);
    },

    stringColumns(model) {
      return model.columns.filter((c) => c.type === 'string');
    },

    hasPaginateStmt(model) {
      return this.stmts.findIndex((s) => s.type === 'paginate' && s.model === model.id) > -1;
    },

    hasGetAllStmt(model) {
      return this.stmts.findIndex((s) => s.type === 'getAll' && s.model === model.id) > -1;
    },

    hasPluckStmt(model, column) {
      return this.stmts.findIndex((s) => s.type === 'pluck' && s.model === model.id && s.column === column.id) > -1;
    },

    hasRelation(relation, model, stmtType) {
      const stmt = this.stmts.find((s) => s.type === stmtType && s.model === model.id);
      if (!stmt) {
        return false;
      }

      if (!stmt.with) {
        return false;
      }

      return stmt.with.findIndex((w) => w.id === relation.id) > -1;
    },

    getModelRelations(model) {
      return this.eloquentRelations.filter((r) => r.source.id === model.id).map((r) => {
        const sourceId = r.source.id;
        const relatedId = r.related.id;
        const { type } = r;

        const source = this.blueprints.find((s) => s.id === sourceId);
        const related = this.blueprints.find((s) => s.id === relatedId);

        if (!source || !related) {
          return null;
        }

        let relationName = this.str.lcFirst(this.str.studly(related.modelName));

        if (type === 'hasMany' || r.intermediateTable || r.columns) {
          relationName = this.str.lcFirst(this.str.studly(this.str.pluralize(related.modelName)));
        }

        return {
          id: r.id,
          source: sourceId,
          related: relatedId,
          type,
          relationName,
        };
      }).filter((r) => r);
    },

    onTypeHintedModelStateChanged(active, model) {
      const typeHintedIndex = this.typeHint.findIndex((t) => t === model.id);
      if (active) {
        if (typeHintedIndex < 0) {
          this.typeHint.push(model.id);
        }
      } else if (typeHintedIndex > -1) {
        this.typeHint.splice(typeHintedIndex, 1);
      }
    },

    onPaginateModelChanged(active, model) {
      const stmtIndex = this.stmts.findIndex((s) => s.type === 'paginate' && s.model === model.id);
      const getAllStmtIndex = this.stmts.findIndex((s) => s.type === 'getAll' && s.model === model.id);
      if (active) {
        if (getAllStmtIndex > -1) {
          this.stmts.splice(getAllStmtIndex, 1);
        }

        if (stmtIndex < 0) {
          this.stmts.push({
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            type: 'paginate',
            model: model.id,
            with: [],
            humanReadable: `Paginate ${this.str.snakeCase(this.str.pluralize(model.modelName))}`,
          });
        }
      } else if (stmtIndex > -1) {
        this.stmts.splice(stmtIndex, 1);
      }
    },

    onGetAllModelsChanged(active, model) {
      const stmtIndex = this.stmts.findIndex((s) => s.type === 'getAll' && s.model === model.id);
      const paginateStmtIndex = this.stmts.findIndex((s) => s.type === 'paginate' && s.model === model.id);
      if (active) {
        if (paginateStmtIndex > -1) {
          this.stmts.splice(paginateStmtIndex, 1);
        }

        if (stmtIndex < 0) {
          this.stmts.push({
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            type: 'getAll',
            model: model.id,
            with: [],
            humanReadable: `Get all ${this.str.snakeCase(this.str.pluralize(model.modelName))}`,
          });
        }
      } else if (stmtIndex > -1) {
        this.stmts.splice(stmtIndex, 1);
      }
    },

    onPluckModelColumnChanged(active, model, column) {
      const stmtIndex = this.stmts.findIndex((s) => s.type === 'pluck' && s.model === model.id && s.column === column.id);
      if (active) {
        if (stmtIndex < 0) {
          this.stmts.push({
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            type: 'pluck',
            model: model.id,
            column: column.id,
            humanReadable: `Pluck ${column.name} from ${model.modelName}`,
          });
        }
      } else if (stmtIndex > -1) {
        this.stmts.splice(stmtIndex, 1);
      }
    },

    onWithRelationStateChanged(active, relation, model, stmtType) {
      const stmt = this.stmts.find((s) => s.type === stmtType && s.model === model.id);
      if (!stmt) {
        return;
      }

      const relationIndex = stmt.with.findIndex((w) => w.id === relation.id);

      if (active) {
        if (relationIndex < 0) {
          stmt.with.push(relation);
        }
      } else if (relationIndex > -1) {
        stmt.with.splice(relationIndex, 1);
      }
    },
  },
};
</script>

<style scoped>

</style>
