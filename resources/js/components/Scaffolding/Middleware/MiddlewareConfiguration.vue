<template>
  <scaffolding-component-container
    heading="Configure Middleware"
    :loading="loading || fetchingMutations"
  >
    <row>
      <column centered>
        <pg-check-box
          v-model="minifyHtml"
          centered
          label="Minify HTML"
          @change="persist"
        />
        <pg-check-box
          v-model="validatePostSize"
          centered
          label="Validate Post Size"
          @change="persist"
        />
        <pg-check-box
          v-model="trimStrings"
          centered
          label="Trim Strings"
          @change="persist"
        />
        <pg-check-box
          v-model="convertEmptyStringsToNull"
          centered
          label="Convert Empty Strings To Null"
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
  name: 'MiddlewareConfiguration',
  components: {
    ScaffoldingComponentContainer,
    PgCheckBox,
    Column,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,

      minifyHtml: true,
      validatePostSize: true,
      trimStrings: true,
      convertEmptyStringsToNull: true,
    };
  },
  async created() {
    this.loading = true;
    const { data } = await this.mutation({ path: 'middlewares' });
    this.loading = false;

    if (!data.value) {
      return;
    }

    this.minifyHtml = data.value.minifyHtml !== undefined ? data.value.minifyHtml : true;
    this.validatePostSize = data.value.validatePostSize !== undefined
      ? data.value.validatePostSize
      : true;
    this.trimStrings = data.value.trimStrings !== undefined ? data.value.trimStrings : true;
    this.convertEmptyStringsToNull = data.value.convertEmptyStringsToNull !== undefined
      ? data.value.convertEmptyStringsToNull
      : true;
  },
  methods: {
    persist() {
      const name = 'Middlewares';
      const path = 'middlewares';
      const value = {
        minifyHtml: this.minifyHtml,
        validatePostSize: this.validatePostSize,
        trimStrings: this.trimStrings,
        convertEmptyStringsToNull: this.convertEmptyStringsToNull,
      };

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
