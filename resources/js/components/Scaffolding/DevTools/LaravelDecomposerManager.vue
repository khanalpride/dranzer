<template>
  <scaffolding-component-container heading="Manage Laravel Decomposer" :loading="loading || fetchingMutations">
    <row>
      <column>
        <blockquote class="hint-text no-margin">
          <a href="https://github.com/lubusIN/laravel-decomposer" target="_blank">
            Laravel Decomposer
          </a>
          decomposes and lists all the installed packages and
          their dependencies along with the Laravel & the Server environment details
          your app is running in. Decomposer also generates a markdown report from those
          details that can be used for troubleshooting purposes, also it allows you to
          generate the same report as an array and also as JSON anywhere in your code
        </blockquote>
      </column>
     <column centered>
       <separator />
       <pg-check-box centered v-model="install" label="Install Laravel Decomposer" @change="persist" />
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
  name: 'LaravelDecomposerManager',
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
      const { data } = await this.mutation({ path: 'devTools/decomposer/install' });
      this.install = data.value && data.value.install !== undefined ? data.value.install : this.install;
    },
    persist() {
      const payload = {
        name: 'Laravel Decomposer',
        path: 'devTools/decomposer/install',
        value: { install: this.install },
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
