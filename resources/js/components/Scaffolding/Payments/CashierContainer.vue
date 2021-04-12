<template>
    <scaffolding-component-container heading="Configure Payments (Cashier)" :loading="loading || fetchingMutations">
      <cashier-stripe />
    </scaffolding-component-container>
</template>

<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';
import CashierStripe from '@/components/Scaffolding/Payments/Providers/CashierStripe';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';

export default {
  name: 'CashierContainer',
  mixins: [asyncImports, mutations],
  components: {
    CashierStripe,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      provider: 'stripe',
    };
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'payments/cashier/provider' });
      this.provider = data.value || this.provider;
    },
    persist() {
      const name = 'Cashier Provider';
      const path = 'payments/cashier/provider';
      const value = this.provider;

      this.mutate(value, name, path);
    },
  },
};
</script>

<style scoped>

</style>
