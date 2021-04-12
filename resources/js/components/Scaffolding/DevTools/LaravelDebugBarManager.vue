<template>
  <scaffolding-component-container heading="Manage Laravel Debug Bar" :loading="loading || fetchingMutations">
    <row>
      <column>
        <blockquote class="hint-text no-margin">
          This is a package to integrate PHP Debug Bar with Laravel.
          It includes a ServiceProvider to register the debugbar and attach it to the output.
          You can publish assets and configure it through Laravel.
          It bootstraps some Collectors to work with Laravel and implements a couple custom DataCollectors, specific for Laravel.
        </blockquote>
      </column>
      <column centered>
        <separator />
        <p class="hint-text no-margin">
          <i class="fa fa-info"></i>
          For a more feature-rich and modern alternative, consider using
          <a href="https://laravel.com/docs/8.x/telescope" target="_blank">Laravel Telescope</a>
          which can be enabled in the queues section.
        </p>
      </column>
     <column centered>
       <separator />
       <pg-check-box centered v-model="install" label="Install Laravel Debug Bar" @change="persist" />
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
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Separator from '@/components/Layout/Separator';

export default {
  name: 'LaravelDebugBarManager',
  mixins: [asyncImports, mutations],
  components: {
    Separator,
    PgCheckBox,
    Row,
    Column,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      install: false,
    };
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'devTools/debugBar/install' });
      this.install = data.value && data.value.install !== undefined ? data.value.install : this.install;
    },
    persist() {
      const payload = {
        name: 'Laravel DebugBar',
        path: 'devTools/debugBar/install',
        value: { install: this.install },
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
