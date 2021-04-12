<template>
  <scaffolding-component-container heading="Manage Controllers" :loading="loading || fetchingMutations">
    <row>
     <column>
       <tabs-manager ref="tabsManager" :tabs="tabs" path="config/controllers/tabs/active">
         <template slot="web">
           <controller-manager type="web" :eloquent-relations="eloquentRelations" show-presets />
         </template>
       </tabs-manager>
     </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import TabsManager from '@/components/Tabs/TabsManager';
import ControllerManager from '@/components/Scaffolding/Controllers/ControllerManager';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

export default {
  name: 'ControllersContainer',
  props: {
    type: String,
  },
  mixins: [mutations, asyncImports],
  components: {
    ControllerManager, TabsManager, Row, Column, ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      eloquentRelations: [],
    };
  },
  computed: {
    tabs() {
      return [
        {
          id: 'web',
          label: 'Web Controllers',
        },
      ];
    },
  },
  async created() {
    this.loading = true;
    await this.syncEloquentRelations();
    this.loading = false;
  },
  methods: {
    async syncEloquentRelations() {
      const { data } = await this.mutation({ path: 'eloquent/relations/regular', like: true, refresh: true });
      this.eloquentRelations = this.getPersistedMutationValue(data);
    },
  },
};
</script>

<style scoped>

</style>
