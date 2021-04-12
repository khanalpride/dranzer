<template>
  <scaffolding-component-container
    heading="Configure Headers and Proxies"
    :loading="loading || fetchingMutations"
  >
    <row>
      <column>
        <basic-content-section heading="Headers">
          <row>
            <column
              size="4"
              offset="4"
            >
              <simple-select
                v-model="selectedHeaders"
                full-width
                placeholder="Choose headers"
                collapse-tags
                multiple
                filterable
                :entities="headers"
                @change="persist"
              >
                <template slot-scope="{ entity }">
                  <el-option
                    :key="entity.value"
                    :label="str.ellipse(entity.label, 20)"
                    :value="entity.value">
                    <template>
                      <span>{{ entity.label }}</span>
                    </template>
                  </el-option>
                </template>
              </simple-select>
            </column>
          </row>
        </basic-content-section>
      </column>

      <column>
        <basic-content-section
          heading="Proxies"
          prepend-separator
        >
          <row>
            <column size="4" offset="4">
              <simple-select full-width
                             multiple
                             filterable
                             collapse-tags
                             allow-create
                             placeholder="Select or create proxies..."
                             :entities="proxies"
                             :value="proxyValues"
                             @change="onProxiesUpdated">
                <template slot-scope="{ entity }">
                  <el-option :key="entity.id"
                             :label="str.ellipse(entity.value, 25)"
                             :value="entity.value">
                    <template>
                      <span>{{ entity.value }}</span>
                    </template>
                  </el-option>
                </template>
              </simple-select>
            </column>
          </row>
        </basic-content-section>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import BasicContentSection from '@/components/Content/BasicContentSection';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import SimpleSelect from '@/components/Select/SimpleSelect';

export default {
  name: 'TrustProxiesManager',
  components: {
    SimpleSelect,
    ScaffoldingComponentContainer,
    BasicContentSection,
    Column,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,

      selectedHeaders: ['HEADER_X_FORWARDED_ALL'],

      headers: [
        {
          label: 'HEADER_FORWARDED',
          value: 'HEADER_FORWARDED',
        },
        {
          label: 'HEADER_X_FORWARDED_ALL',
          value: 'HEADER_X_FORWARDED_ALL',
        },
        {
          label: 'HEADER_X_FORWARDED_AWS_ELB',
          value: 'HEADER_X_FORWARDED_AWS_ELB',
        },
        {
          label: 'HEADER_X_FORWARDED_FOR',
          value: 'HEADER_X_FORWARDED_FOR',
        },
        {
          label: 'HEADER_X_FORWARDED_HOST',
          value: 'HEADER_X_FORWARDED_HOST',
        },
        {
          label: 'HEADER_X_FORWARDED_PORT',
          value: 'HEADER_X_FORWARDED_PORT',
        },
        {
          label: 'HEADER_X_FORWARDED_PROTO',
          value: 'HEADER_X_FORWARDED_PROTO',
        },
      ],

      proxies: [],
    };
  },
  computed: {
    proxyValues() {
      return this.proxies.map((p) => p.value);
    },
  },
  async created() {
    this.loading = true;
    const { data } = await this.mutation({ path: 'request/proxies' });
    this.loading = false;

    this.selectedHeaders = data.value
      ? data.value.headers
      : this.selectedHeaders;

    this.proxies = data.value ? data.value.proxies : [];
  },
  methods: {
    addProxy() {
      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);
      this.proxies.push({
        id,
        proxy: '',
      });

      this.$nextTick(() => {
        if (this.$refs[id]) {
          this.$refs[id].focus();
        }
      });
    },

    deleteProxy(proxy) {
      const pIndex = this.proxies.findIndex((p) => p.id === proxy.id);
      if (pIndex > -1) {
        this.proxies.splice(pIndex, 1);
        this.persist();
      }
    },

    persist() {
      const name = 'Trust Proxies';
      const path = 'request/proxies';
      const value = {
        headers: this.selectedHeaders,
        proxies: this.proxies.filter((p) => p.value.trim() !== ''),
      };

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },

    onProxiesUpdated(proxies) {
      this.proxies = proxies.map((p) => ({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        value: p,
      }));
      this.persist();
    },
  },
};
</script>

<style scoped></style>
