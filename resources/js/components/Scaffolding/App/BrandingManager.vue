<template>
  <scaffolding-component-container heading="Branding" :loading="loading || fetchingMutations">
    <row>
     <column size="10" offset="1">
       <row>
         <column size="4">
           <form-input-title title="App Name" />
           <pg-input v-model="name" />
         </column>
         <column size="8">
           <form-input-title title="App Description" />
           <pg-input v-model="desc" />
         </column>
       </row>
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
import PgInput from '@/components/Forms/PgInput';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import { mapState } from 'vuex';

export default {
  name: 'BrandingManager',
  mixins: [asyncImports, mutations],
  components: {
    FormInputTitle,
    PgInput,
    Row,
    Column,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      name: '',
      desc: '',
    };
  },
  computed: {
    ...mapState('project', ['project']),

    persistable() {
      return {
        name: this.name,
        desc: this.desc,
      };
    },
  },
  watch: {
    persistable: {
      handler() {
        this.persist();
      },
    },
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;

    if (!this.name || this.name.trim() === '') {
      this.name = this.project.name;
    }
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'config/app/branding' });

      const value = data.value || {};

      this.name = value.name;
      this.desc = value.desc;
    },
    persist() {
      const payload = {
        name: 'Branding',
        path: 'config/app/branding',
        value: this.persistable,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
