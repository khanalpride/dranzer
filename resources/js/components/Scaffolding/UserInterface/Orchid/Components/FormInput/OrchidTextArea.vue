<template>
  <row>
    <column size="12">
      <form-input-title :centered="false" small title="Row Count" />
      <el-input-number :disabled="project && project.downloaded"
                       v-model="rows" size="small" :min="1" :max="25" />
    </column>
  </row>
</template>

<script>
import { mapState } from 'vuex';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import FormInputTitle from '@/components/Typography/FormInputTitle';

export default {
  name: 'OrchidTextArea',
  props: {
    tableName: String,
    column: {},
    persisted: {},
  },
  components: {
    FormInputTitle,
    Row,
    Column,
  },
  data() {
    const persisted = this.persisted || {};

    return {
      rows: persisted.rows || 3,
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
    ...mapState('project', ['project']),

    broadcastable() {
      return {
        rows: this.rows,
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
