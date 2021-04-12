<template>
  <scaffolding-component-container heading="Manage Laravel IDE Helper" :loading="loading || fetchingMutations">
    <row>
      <column>
        <blockquote class="hint-text no-margin">
          <a href="https://github.com/barryvdh/laravel-ide-helper" target="_blank">
            Laravel IDE Helper
          </a>
          generates helper files that enable your IDE to provide accurate autocompletion.
          Generation is done based on the files in your project, so they are always up-to-date.
        </blockquote>
      </column>
     <column centered>
       <separator />
       <pg-check-box centered v-model="install" label="Install Laravel IDE Helper" @change="persist" />
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
  name: 'LaravelIdeHelperManager',
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
      const { data } = await this.mutation({ path: 'devTools/ideHelper/install' });
      this.install = data.value && data.value.install !== undefined ? data.value.install : this.install;
    },
    persist() {
      const payload = {
        name: 'Laravel DebugBar',
        path: 'devTools/ideHelper/install',
        value: { install: this.install },
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
