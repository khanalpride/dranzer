<template>
  <scaffolding-component-container
    heading="Configure Telescope"
    :loading="loading || fetchingMutations"
  >
    <row>
      <column centered>
        <pg-check-box
          v-model="enabled"
          centered
          label="Install Telescope"
          @change="persist"
        />
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';

export default {
  name: 'TelescopeManager',
  components: {
    PgCheckBox,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,

      enabled: false,
    };
  },
  async created() {
    this.loading = true;
    await this.syncIsEnabled();
    this.loading = false;
  },
  methods: {
    async syncIsEnabled() {
      const { data } = await this.mutation({
        path: 'logging/telescope/enabled',
      });
      this.enabled = data.value !== null ? data.value : this.enabled;
    },
    persist() {
      const name = 'Telescope';
      const path = 'logging/telescope/enabled';
      const value = this.enabled;

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped></style>
