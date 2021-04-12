<template>
  <row>
    <column>
      <pg-check-box v-model="sendUncheckedToServer" label="Send Unchecked State To Server" />
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';

export default {
  name: 'OrchidCheckbox',
  props: {
    tableName: String,
    column: {},
    persisted: {},
  },
  components: {
    PgCheckBox,
    Row,
    Column,
  },
  data() {
    const persisted = this.persisted || {};

    return {
      sendUncheckedToServer: persisted.sendUncheckedToServer !== undefined
        ? persisted.sendUncheckedToServer
        : true,
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
    broadcastable() {
      return {
        sendUncheckedToServer: this.sendUncheckedToServer,
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
