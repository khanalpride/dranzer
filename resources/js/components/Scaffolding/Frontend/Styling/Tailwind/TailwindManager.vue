<template>
  <scaffolding-component-container heading="Configure Tailwind" :loading="loading || fetchingMutations">
    <row>
      <column centered>
        <pg-check-box v-model="enabled" no-margin centered label="Install Tailwind" @change="persistIsEnabled" />
      </column>
     <column v-if="enabled">
       <separator />
       <tailwind-theme-config />
     </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import TailwindThemeConfig from '@/components/Scaffolding/Frontend/Styling/Tailwind/TailwindThemeConfig';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Separator from '@/components/Layout/Separator';

export default {
  name: 'TailwindManager',
  mixins: [asyncImports, mutations],
  components: {
    Separator,
    PgCheckBox,
    TailwindThemeConfig,
    Row,
    Column,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      enabled: false,

      tabs: [
        {
          id: 'theme',
          label: 'Theme',
        },
        {
          id: 'breakpoints',
          label: 'Breakpoints',
        },
        {
          id: 'colors',
          label: 'Colors',
        },
        {
          id: 'spacing',
          label: 'Spacing',
        },
        {
          id: 'variants',
          label: 'Variants',
        },
      ],
    };
  },
  computed: {

  },
  async created() {
    this.loading = true;
    await this.syncIsEnabled();
    this.loading = false;
  },
  methods: {
    async syncIsEnabled() {
      const { data } = await this.mutation({ path: 'config/tailwind/enabled' });
      // noinspection PointlessBooleanExpressionJS
      this.enabled = data.value && data.value !== undefined ? data.value : this.enabled;
    },

    persistIsEnabled(enabled) {
      this.mutate({ name: 'Tailwind Config', path: 'config/tailwind/enabled', value: enabled });
    },
  },
};
</script>

<style scoped>

</style>
