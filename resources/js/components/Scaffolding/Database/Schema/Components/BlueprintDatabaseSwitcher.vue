<template>
  <row>
    <column centered>
      <!--suppress HtmlUnknownAttribute -->
      <a
        href="#"
        class="text-complete"
        v-tooltip.right.15
        content="Change database driver"
        @click.prevent="onChangeDatabase"
      ><i class="fa fa-database text-info" />
        <span class="bold text-complete hint-text">{{ databaseType }}</span></a
      >
    </column>

    <column>
      <separator />
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import { mapState } from 'vuex';

export default {
  name: 'BlueprintDatabaseSwitcher',
  components: { Separator, Row, Column },
  computed: {
    ...mapState('project', ['mutations', 'projectComponent']),
    databaseType() {
      const dbTypeMutation = this.mutations.find((m) => m.path === 'database/type');
      return dbTypeMutation && dbTypeMutation.value ? dbTypeMutation.value : 'MySQL';
    },
  },
  methods: {
    onChangeDatabase() {
      if (this.projectComponent) {
        this.projectComponent.activateModule('manage-database');
      }
    },
  },
};
</script>

<style scoped>

</style>
