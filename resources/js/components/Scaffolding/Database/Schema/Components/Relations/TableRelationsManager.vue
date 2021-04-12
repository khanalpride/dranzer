<template>
  <content-container col-size="10" col-offset="1">
    <row v-if="loading">
      <column centered>
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column push5 centered size="11" class="text-info hint-text" v-if="relations.length">
        <row>
          <column size="3">
            <p>Local Column</p>
          </column>
          <column size="3">
            <p>References Table</p>
          </column>
          <column size="3">
            <p>On Column</p>
          </column>
          <column size="3">
            <p>On Delete</p>
          </column>
        </row>
      </column>
      <column :push5="index > 0" :key="relation.id" v-for="(relation, index) in relations">
        <table-relation :ref="relation.id"
                         :blueprint="blueprint"
                         :blueprints="blueprints"
                         :persisted="relation"
                         @updated="onRelationUpdated($event, relation)"
                         @delete="deleteRelation(relation)" />
      </column>
      <column push5>
        <simple-button color-class="primary" @click="addRelation">
          <i class="fa fa-plus"></i>
        </simple-button>
      </column>
    </row>
  </content-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ContentContainer from '@/components/Content/ContentContainer';
import TableRelation from '@/components/Scaffolding/Database/Schema/Components/Relations/TableRelation';
import mutations from '@/mixins/mutations';
import entity from '@/mixins/entity';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';

export default {
  name: 'TableRelationsManager',
  mixins: [mutations, entity],
  props: {
    blueprint: {},
    blueprints: Array,
    options: {},
  },
  components: {
    SimpleButton,
    IndeterminateProgressBar,
    Row,
    Column,
    TableRelation,
    ContentContainer,
  },
  data() {
    return {
      loading: false,

      relations: [],
    };
  },
  watch: {
    blueprint: {
      handler(blueprint) {
        this.validateRelations(blueprint);
      },
      deep: true,
      immediate: true,
    },
  },
  async created() {
    this.loading = true;
    await this.syncRelations();
    this.validateRelations(this.blueprint);
    this.broadcastRelationCountChanged();
    this.loading = false;
  },
  methods: {
    async syncRelations() {
      const { data } = await this.mutation({ path: `database/relations/${this.blueprint.id}`, like: true, refresh: true });
      this.relations = this.getPersistedMutationValue(data);
    },

    addRelation(data) {
      let relation = {
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
      };

      if (data) {
        const rel = this.relations.find(
          (r) => r.localColumn === data.localColumn
            && r.localTable === data.localTable
            && r.foreignColumn === data.foreignColumn
            && r.foreignTable === data.foreignTable,
        );

        if (rel) {
          return;
        }

        relation = { ...relation, ...data };
      }

      this.relations.push(relation);

      this.broadcastRelationCountChanged();

      this.$nextTick(() => {
        if (data) {
          this.$refs[relation.id][0].sync(data);
        }
      });
    },

    async deleteRelation(relation) {
      const relationIndex = this.relations.findIndex((r) => r.id === relation.id);
      if (relationIndex > -1) {
        await this.deleteMutation(`database/relations/${this.blueprint.id}/${relation.id}`, {
          then: () => {
            this.relations.splice(relationIndex, 1);
            this.broadcastRelationCountChanged();
          },
        });
      }
    },

    validateRelations(blueprint) {
      const { columns } = blueprint;

      const { relations } = this;

      relations.forEach((rel) => {
        const { localColumn, foreignColumn, foreignTable } = rel;

        const locCol = columns.find((c) => c.id === localColumn && c.attributes.us);

        const foreignSchema = this.blueprints.find((s) => s.id === foreignTable);

        if (!locCol || !foreignSchema) {
          this.deleteRelation(rel);
        } else {
          const foreignCol = foreignSchema.columns.find((c) => c.id === foreignColumn && c.attributes.us);
          if (!foreignCol) {
            this.deleteRelation(rel);
          }
        }

        this.broadcastRelationCountChanged();
      });
    },

    broadcastRelationCountChanged() {
      this.$emit('count-changed', this.relations.length);
    },

    onRelationUpdated(update, relation) {
      const relationIndex = this.relations.findIndex((r) => r.id === relation.id);
      if (relationIndex > -1) {
        this.relations[relationIndex].localColumn = relation.localColumn;
        this.relations[relationIndex].localTable = relation.localTable;
        this.relations[relationIndex].foreignColumn = relation.foreignColumn;
        this.relations[relationIndex].foreignTable = relation.foreignTable;
      }
    },
  },
};
</script>

<style scoped>

</style>
