<template>
  <scaffolding-component-container
    :heading="`One To One Relations (${relations.length})`"
    :loading="loading || fetchingMutations">
    <div v-if="!groups.length">
      <p class="text-info hint-text text-center no-margin"><i class="fa fa-info"></i>
        No relations can be generated at this time. Please check the docs, re-visit the schema, and then try again.
      </p>
    </div>
    <div v-else>
      <row>
        <column size="8" offset="2">
          <pg-input v-model="query" placeholder="Search for possible one-to-one relations"/>
        </column>
        <column push5 size="8" offset="2">
          <p class="small text-info hint-text">e.g. User has one Product</p>
        </column>
      </row>
      <row>
        <column push5 size="8"
                offset="2">
          <p class="small text-primary">
            Showing <strong>{{ filteredGroups.length * 2 }}</strong> / <strong>{{ groups.length * 2 }}</strong> available
            relations<span v-if="relations.length"> (<strong>{{ relations.length }}</strong> enabled)</span>
          </p>
        </column>
        <column size="8" offset="2">
          <pg-check-box :value="allRelationsEnabled" :disabled="processingToggleAll" @change="onToggleCheckAll" />
        </column>
        <column :push5="index === 0"
                :key="group.id"
                size="8"
                offset="2"
                v-for="(group, index) in filteredGroups">
          <row>
            <column v-if="index > 0">
              <separator/>
            </column>
            <column :key="rel.source.id + rel.related.id + rel.type" v-for="rel in group.relations">
              <pg-check-box no-margin
                            v-model="rel.enabled"
                            :disabled="processingToggleAll"
                            @change="onGroupedRelationToggled($event, rel)"
                            v-if="rel.type === 'hasOne'">
                <template slot="label">
                  <strong>{{ rel.source.name }}</strong> <span class="text-complete bold">has one</span> <strong>{{
                    rel.related.name
                  }}</strong>
                </template>
              </pg-check-box>
              <pg-check-box no-margin
                            v-model="rel.enabled"
                            :disabled="processingToggleAll"
                            @change="onGroupedRelationToggled($event, rel)" v-else>
                <template slot="label">
                  <strong>{{ rel.source.name }}</strong> <span class="text-complete bold">belongs to</span>
                  <strong>{{ rel.related.name }}</strong>
                </template>
              </pg-check-box>
            </column>
          </row>
        </column>
      </row>
    </div>
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

export default {
  name: 'OneToOneRegularRelationsContainer',
  mixins: [asyncImports, mutations],
  props: {
    models: Array,
  },
  components: {
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
    };
  },
  computed: {
    filteredGroups() {
      const query = this.query.trim().toLowerCase();

      if (query === '') {
        return this.groups;
      }

      return this.groups.filter((g) => g.relations.find((rel) => {
        const hasOneStr = `${rel.source.name} has one ${rel.related.name}`.toLowerCase();
        const belongsToStr = `${rel.source.name} belongs to ${rel.related.name}`.toLowerCase();

        try {
          return (new RegExp(query).test(hasOneStr)) || (new RegExp(query).test(belongsToStr));
        } catch (e) {
          return null;
        }
      }));
    },

    allRelationsEnabled() {
      return this.groups.map((g) => g.relations).flat().length === this.relations.length;
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
        path: 'eloquent/relations/regular/one-to-one',
        like: true,
        refresh: true,
      });

      this.relations = data.value ? data.value.map((v) => v.value) : this.relations;
    },

    getGroups() {
      const { models } = this;

      const groups = [];

      models.forEach((m) => {
        const modelId = m.id;
        const modelName = m.modelName.trim();
        const otherModels = models.filter((mod) => mod.id !== m.id);
        otherModels.forEach((otherModel) => {
          const otherModelColumn = otherModel.columns.find((c) => c.attributes.us && c.name.toLowerCase() === `${modelName.toLowerCase()}_id`);
          if (otherModelColumn) {
            const hasOneRel = this.relations.find((r) => r.source.id === modelId && r.related.id === otherModel.id && r.type === 'hasOne');
            const belongsToRel = this.relations.find((r) => r.source.id === otherModel.id && r.related.id === modelId && r.type === 'belongsTo');
            groups.push({
              id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
              relations: [
                {
                  enabled: hasOneRel || false,
                  source: {
                    id: modelId,
                    name: modelName,
                  },
                  related: {
                    id: otherModel.id,
                    name: otherModel.modelName,
                  },
                  type: 'hasOne',
                }, {
                  enabled: belongsToRel || false,
                  source: {
                    id: otherModel.id,
                    name: otherModel.modelName,
                  },
                  related: {
                    id: modelId,
                    name: modelName,
                  },
                  type: 'belongsTo',
                },
              ],
            });
          }
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

    persist(relation) {
      this.$nextTick(() => {
        const sourceModel = relation.source;
        const relatedModel = relation.related;

        const name = 'One To One Relation';
        const path = `eloquent/relations/regular/one-to-one/${sourceModel.id}/${relatedModel.id}`;

        const payload = {
          name,
          path,
          value: relation,
        };

        this.mutate(payload);
      });
    },

    onGroupedRelationToggled(checked, relation) {
      if (!checked) {
        const relationIndex = this.relations.findIndex(
          (r) => r.source.id === relation.source.id
            && r.related.id === relation.related.id
            && r.type === relation.type,
        );
        if (relationIndex > -1) {
          this.deleteMutation(`eloquent/relations/regular/one-to-one/${relation.source.id}/${relation.related.id}`, {
            then: () => this.relations.splice(relationIndex, 1),
          });
        }
      } else {
        const newRelation = {
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          source: relation.source,
          related: relation.related,
          type: relation.type,
        };
        this.relations.push(newRelation);
        this.persist(newRelation);
      }
    },

    onToggleCheckAll(checkAll) {
      const allMutations = [];

      this.groups.map((g) => g.relations).flat().forEach((relation) => {
        relation.enabled = true;
        allMutations.push({
          name: `One To One Relation (${relation.source.id} & ${relation.related.id})`,
          path: `eloquent/relations/regular/one-to-one/${relation.source.id}/${relation.related.id}`,
          value: {
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            source: relation.source,
            related: relation.related,
            type: relation.type,
          },
        });
      });

      this.processingToggleAll = true;

      if (checkAll) {
        this.mutate({
          bulk: true,
          name: 'One To One Relations',
          path: 'eloquent/relations/regular/one-to-one/*',
          value: allMutations,
        });
        this.relations = allMutations.map((m) => m.value);
        this.processingToggleAll = false;
      } else {
        const paths = allMutations.map((m) => m.path);
        this.bulkDeleteMutations(paths, {
          then: () => {
            allMutations.forEach((m) => {
              const relationIndex = this.relations.findIndex(
                (r) => r.source.id === m.value.source.id
                && r.related.id === m.value.related.id
                && r.type === m.value.type,
              );

              if (relationIndex > -1) {
                this.relations.splice(relationIndex, 1);

                const groupedRelation = this.groups.map((g) => g.relations).flat().find(
                  (r) => r.source.id === m.value.source.id
                    && r.related.id === m.value.related.id
                    && r.type === m.value.type,
                );

                groupedRelation.enabled = false;
              }
            });

            this.processingToggleAll = false;
          },
        });
      }
    },
  },
};
</script>

<style scoped></style>
