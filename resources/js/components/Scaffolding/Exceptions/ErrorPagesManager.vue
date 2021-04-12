<template>
  <scaffolding-component-container heading="Configure Error Messages" :loading="loading || fetchingMutations">
    <row>
      <column>
        <row>
          <column>
            <pg-check-box no-margin
                          label="Use Minimal Template"
                          class="p-t-5"
                          v-model="basic"
                          @change="persist">
            </pg-check-box>
          </column>
          <column>
            <pg-check-box color-class="green"
                          no-margin
                          class="p-t-5"
                          :value="1"
                          disabled
                          label="Indicates that the associated blade file will be updated and published" />
          </column>
        </row>
      </column>
      <column :push10="index === 0" :key="message.code" v-for="(message, index) in messages">
        <row>
          <column>
            <pg-check-box no-margin color-class="green" class="p-t-5" disabled :value="shouldOverride(message.code)">
              <template slot="label">
                <span class="bold" :class="{'text-green': shouldOverride(message.code)}">{{ message.code }}</span>
              </template>
            </pg-check-box>
          </column>
          <column push5 size="10" class="m-l-30">
            <row>
              <column size="4">
                <form-input-title :centered="false" title="Heading / Title" />
                <pg-input v-model="message.title" />
              </column>
              <column size="8">
                <form-input-title :centered="false" title="Message" />
                <pg-input v-model="message.message" />
              </column>
            </row>
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
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import PgInput from '@/components/Forms/PgInput';

export default {
  name: 'ErrorPagesManager',
  mixins: [asyncImports, mutations],
  components: {
    PgInput,
    FormInputTitle,
    PgCheckBox,
    Row,
    Column,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      basic: false,

      messages: [
        {
          code: 401,
          title: 'Unauthorized',
          message: 'Unauthorized',
        },
        {
          code: 403,
          title: 'Forbidden',
          message: 'Forbidden',
        },
        {
          code: 404,
          title: 'Not Found',
          message: 'Not Found',
        },
        {
          code: 419,
          title: 'Page Expired',
          message: 'Page Expired',
        },
        {
          code: 429,
          title: 'Too Many Requests',
          message: 'Too Many Requests',
        },
        {
          code: 500,
          title: 'Server Error',
          message: 'Server Error',
        },
        {
          code: 503,
          title: 'Service Unavailable',
          message: 'Service Unavailable',
        },
      ],
    };
  },
  watch: {
    messages: {
      handler() {
        this.persist();
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'exceptions/pages' });
      const messages = data.value ? (data.value.messages || []) : [];
      this.messages = messages.length ? messages : this.messages;
    },

    shouldOverride(code) {
      const m = this.messages.find((message) => message.code === code);

      const title = m.title.trim().toLowerCase();
      const message = m.message.trim().toLowerCase();

      if (code === 401) {
        return title !== 'unauthorized' || message !== 'unauthorized';
      }
      if (code === 403) {
        return title !== 'forbidden' || message !== 'forbidden';
      }
      if (code === 404) {
        return title !== 'not found' || message !== 'not found';
      }
      if (code === 419) {
        return title !== 'page expired' || message !== 'page expired';
      }
      if (code === 429) {
        return title !== 'too many requests' || message !== 'too many requests';
      }
      if (code === 500) {
        return title !== 'server error' || message !== 'server error';
      }
      if (code === 503) {
        return title !== 'service unavailable' || message !== 'service unavailable';
      }

      return false;
    },

    persist() {
      const payload = {
        name: 'Error Pages',
        path: 'exceptions/pages',
        value: {
          basic: this.basic,
          messages: this.messages.map((m) => ({ ...m, override: this.shouldOverride(m.code) })),
        },
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
