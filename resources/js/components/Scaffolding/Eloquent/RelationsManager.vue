<template>
  <scaffolding-component-container
    heading="Configure Basic Model Relations"
    :loading="loading || fetchingMutations"
  >
    <row v-if="models.length < 2">
      <column centered>
        <p class="text-primary no-margin">
          <i class="fa fa-exclamation-triangle"></i> You must have at-least 2 models to create relations.
        </p>
      </column>
    </row>
    <row v-else>
      <column>
        <tabs-manager
          path="eloquent/relations/activeTab"
          :tabs="regularRelationTypes"
        >
          <template :slot="type.id" v-for="type in regularRelationTypes">
            <component :is="type.component" :key="type.name" :models="models" />
          </template>
        </tabs-manager>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import ContentCard from '@/components/Cards/ContentCard';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import OneToOneRegularRelationsContainer from '@/components/Scaffolding/Eloquent/Relations/Regular/OneToOneRegularRelationsContainer';
import OneToManyRegularRelationsContainer from '@/components/Scaffolding/Eloquent/Relations/Regular/OneToManyRegularRelationsContainer';
import ManyToManyRegularRelationsContainer from '@/components/Scaffolding/Eloquent/Relations/Regular/ManyToManyRegularRelationsContainer';
import HasOneThroughRegularRelationsContainer from '@/components/Scaffolding/Eloquent/Relations/Regular/HasOneThroughRegularRelationsContainer';
import HasManyThroughRegularRelationsContainer from '@/components/Scaffolding/Eloquent/Relations/Regular/HasManyThroughRegularRelationsContainer';
import TabsManager from '@/components/Tabs/TabsManager';

export default {
  name: 'RelationsManager',
  mixins: [asyncImports, mutations],
  components: {
    TabsManager,
    OneToOneRegularRelationsContainer,
    ScaffoldingComponentContainer,
    IndeterminateProgressBar,
    Column,
    ContentCard,
    Row,
  },
  data() {
    return {
      loading: false,

      models: [],

      regularRelationTypes: [
        {
          id: 'one-to-one',
          name: 'One To One',
          component: OneToOneRegularRelationsContainer,
        },
        {
          id: 'one-to-many',
          name: 'One To Many',
          component: OneToManyRegularRelationsContainer,
        },
        {
          id: 'has-one-through',
          name: 'Has One Through',
          component: HasOneThroughRegularRelationsContainer,
        },
        {
          id: 'has-many-through',
          name: 'Has Many Through',
          component: HasManyThroughRegularRelationsContainer,
        },
        {
          id: 'many-to-many',
          name: 'Many To Many',
          component: ManyToManyRegularRelationsContainer,
        },
      ],
    };
  },
  async created() {
    this.loading = true;

    const models = await this.getModels();

    this.models = models.filter((m) => m.visible !== false && m.modelName);

    this.loading = false;
  },
  methods: {
    async getModels() {
      const { data } = await this.mutation({
        path: 'database/blueprints',
        like: true,
        refresh: true,
      });

      return data.value ? data.value.map((v) => v.value) : [];
    },
  },
};
</script>

<style scoped></style>
