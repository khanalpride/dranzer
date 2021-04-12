<template>
  <scaffolding-component-container heading="Manage Custom Blade Partials" :loading="loading || fetchingMutations">
    <row>
     <column>
       <tabs-manager :tabs="tabs" path="config/ui/custom/blade/partials/active">
         <template :slot="tab.id" v-for="tab in tabs">
           <component :key="tab.id"
                      :is="tab.component"
                      :views="views"
                      :controllers="controllers"
                      :relations="relations"
                      :blueprints="blueprints" />
         </template>
       </tabs-manager>
     </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import TabsManager from '@/components/Tabs/TabsManager';
import NavigationPartials from '@/components/Scaffolding/UserInterface/Custom/Blade/Partials/NavigationPartials';
import sharedMutations from '@/mixins/shared_mutations';
import AlertPartials from '@/components/Scaffolding/UserInterface/Custom/Blade/Partials/AlertPartials';
import CustomBladePartials from '@/components/Scaffolding/UserInterface/Custom/Blade/Partials/CustomBladePartials';

export default {
  name: 'CustomBladePartialsManager',
  mixins: [asyncImports, mutations, sharedMutations],
  components: {
    TabsManager, Row, Column, ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      views: [],

      controllers: [],

      blueprints: [],

      relations: [],

      tabs: [
        {
          id: 'nav',
          label: 'Navigation',
          component: NavigationPartials,
        },
        {
          id: 'alerts',
          label: 'Alerts',
          component: AlertPartials,
        },
        {
          id: 'custom',
          label: 'Custom',
          component: CustomBladePartials,
        },
      ],
    };
  },
  computed: {

  },
  async created() {
    this.loading = true;
    await this.assignControllers();
    await this.assignBlueprints();
    await this.syncViews();
    await this.syncEloquentRelations();
    this.loading = false;
  },
  methods: {
    async syncViews() {
      const { data } = await this.mutation({ path: 'ui/views/', like: true, refresh: true });
      this.views = data.value ? data.value.map((v) => v.value) : [];
    },

    async syncEloquentRelations() {
      const { data } = await this.mutation({ path: 'eloquent/relations/regular', like: true, refresh: true });
      this.relations = this.getPersistedMutationValue(data);
    },
  },
};
</script>

<style scoped>

</style>
