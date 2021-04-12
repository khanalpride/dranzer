<template>
  <row>
    <column size="11">
      <row>
        <column size="3">
          <el-select class="el-sel-full-width" v-model="localColumn">
            <el-option :key="column.id" :value="column.id" :label="column.name" v-for="column in localColumns" />
          </el-select>
        </column>
        <column size="3">
          <el-select class="el-sel-full-width" v-model="foreignTable">
            <el-option :key="blueprint.id" :value="blueprint.id" :label="blueprint.tableName" v-for="blueprint in validBlueprints" />
          </el-select>
        </column>
        <column size="3">
          <el-select class="el-sel-full-width" v-model="foreignColumn">
            <el-option :key="column.id" :value="column.id" :label="column.name" v-for="column in foreignColumns" />
          </el-select>
        </column>
        <column size="3">
          <el-select class="el-sel-full-width" v-model="onDeleteReference">
            <el-option :key="option" :value="option" :label="option" v-for="option in referenceOptions" />
          </el-select>
        </column>
      </row>
    </column>
    <column size="1">
      <button class="btn btn-danger btn-max-height pull-right"
              @click="$emit('delete')">
        <i class="fa fa-close"></i>
      </button>
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import mutations from '@/mixins/mutations';

export default {
  name: 'SchemaRelation',
  mixins: [mutations],
  props: {
    persisted: {},
    blueprint: {},
    blueprints: {},
  },
  components: {
    Row,
    Column,
  },
  data() {
    const persisted = JSON.parse(JSON.stringify(this.persisted || {}));

    return {
      localColumn: persisted.localColumn || null,
      localTable: this.blueprint.id,
      foreignColumn: persisted.foreignColumn || null,
      foreignTable: persisted.foreignTable || null,

      onDeleteReference: persisted.onDeleteReference || 'CASCADE',
      referenceOptions: ['RESTRICT', 'CASCADE', 'SET NULL', 'NO ACTION', 'SET DEFAULT'],
    };
  },
  computed: {
    persistable() {
      return this.getPersistable();
    },

    localColumns() {
      return this.blueprint.columns.filter((c) => c.name && c.name.trim() !== '' && c.attributes.us);
    },

    foreignColumns() {
      if (!this.foreignTable) {
        return [];
      }

      const blueprint = this.blueprints.find((s) => s.id === this.foreignTable);

      if (!blueprint) {
        return [];
      }

      return blueprint.columns.filter((c) => c.name && c.name.trim() !== '' && c.attributes.us);
    },

    validBlueprints() {
      // && s.tableName.trim() !== this.blueprint.tableName.trim()
      return this.blueprints.filter(
        (s) => s.tableName && s.tableName.trim() !== '',
      );
    },
  },
  watch: {
    persistable: {
      handler(v) {
        this.persist(v);
      },
      deep: true,
    },
  },
  methods: {
    getPersistable() {
      return {
        id: this.persisted.id,
        localColumn: this.localColumn,
        localTable: this.localTable,
        foreignColumn: this.foreignColumn,
        foreignTable: this.foreignTable,
        onDeleteReference: this.onDeleteReference,
      };
    },

    sync(relationData) {
      const data = JSON.parse(JSON.stringify(relationData || {}));

      this.localColumn = data.localColumn || this.localColumn;
      this.localTable = data.localTable || this.localTable;
      this.foreignColumn = data.foreignColumn || this.foreignColumn;
      this.foreignTable = data.foreignTable || this.foreignTable;

      this.$nextTick(() => {
        this.persist(this.getPersistable());
      });
    },

    persist(value) {
      this.mutate({
        name: 'Relation',
        path: `database/relations/${this.blueprint.id}/${this.persisted.id}`,
        value,
      });
    },
  },
};
</script>

<style>
div.el-select.rel-dd > div > input {
  border: solid 1px #f1f2f5 !important;
}
</style>

<style scoped>

</style>
