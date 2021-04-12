<template>
    <scaffolding-component-container heading="Configure Horizon" :loading="loading || fetchingMutations">
        <row>
            <column centered>
                <pg-check-box centered v-model="enabled" label="Install Horizon" @change="persistIsEnabled" />
            </column>
        </row>
    </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';

export default {
  name: 'HorizonManager',
  mixins: [asyncImports, mutations],
  components: {
    PgCheckBox, ScaffoldingComponentContainer, Column, Row,
  },
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
      const { data } = await this.mutation({ path: 'queues/horizon/enabled' });
      this.enabled = data.value !== null ? data.value : this.enabled;
    },
    persistIsEnabled() {
      const name = 'Horizon';
      const path = 'queues/horizon/enabled';
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

<style scoped>

</style>
