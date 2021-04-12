<template>
    <content-container col-size="6" col-offset="3">
        <row>
            <column>
                <pg-input :disabled="loading"
                          ref="searchInput"
                          v-model="query" placeholder="Search (js-path supported)..."/>
            </column>
            <column push5 v-if="loading">
                <indeterminate-progress-bar />;
            </column>
            <column v-else>
                <row>
                    <column push10 centered class="p-b-10">
                        <text-block color="primary" bold v-if="mutations.length">
                            Mutations
                            ({{
                                filteredMutations.length
                                + (filteredMutations.length !== mutations.length ? ` / ${mutations.length}` : '')
                            }} / {{ paginatedResponse ? (paginatedResponse.total || mutations.length) : mutations.length }})
                        </text-block>
                        <text-block danger hinted v-else>
                            No mutations found!
                        </text-block>
                    </column>
                    <column centered v-if="paginatedResponse && paginatedResponse.last_page && paginatedResponse.last_page > 1" class="p-b-10">
                        <simple-button :key="page"
                                       :disabled="page === activePage"
                                       @click="syncMutations(page)"
                                       v-for="page in range(1, paginatedResponse.last_page)">
                            {{ page }}
                        </simple-button>
                    </column>
                    <column :push10="index > 0" :key="mutation.uuid" v-for="(mutation, index) in filteredMutations">
                        <content-card :heading="mutation.name" removable @delete="deleteMutation(mutation)">
                            <json-viewer :value="mutation" :expand-depth="4"/>
                        </content-card>
                    </column>
                </row>
            </column>
        </row>
    </content-container>
</template>

<script>
import jspath from 'jspath';

import JsonViewer from 'vue-json-viewer';

import ContentContainer from '@/components/Content/ContentContainer';
import ContentCard from '@/components/Cards/ContentCard';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgInput from '@/components/Forms/PgInput';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';
import TextBlock from '@/components/Typography/Decorated/TextBlock';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

const { axios } = window;

export default {
  name: 'MutationsViewer',
  components: {
    IndeterminateProgressBar,
    TextBlock,
    SimpleButton,
    PgInput,
    Row,
    Column,
    ContentCard,
    ContentContainer,
    JsonViewer,
  },
  data() {
    return {
      loading: false,
      query: '',
      mutations: [],
      activePage: 1,
      paginatedResponse: null,
    };
  },
  computed: {
    filteredMutations() {
      return this.mutations.filter((m) => {
        let result = [];
        try {
          const query = this.query ? this.query.trim() : '';
          result = jspath.apply(query === '' ? '.' : query, m);
        } catch (e) {
          return false;
        }
        return result.length;
      });
    },
  },
  async created() {
    await this.syncMutations();
    this.focusSearchInput();
  },
  methods: {
    async syncMutations(page = 1) {
      this.loading = true;
      const { data } = await axios.post('/mutations/debug', { page });
      this.loading = false;

      this.paginatedResponse = data.mutations || {};

      this.activePage = this.paginatedResponse.current_page;

      this.mutations = this.paginatedResponse.data;
    },
    async deleteMutation(mutation) {
      const mutationIndex = this.mutations.findIndex((m) => m.uuid === mutation.uuid);

      if (mutationIndex < 0) {
        return;
      }

      const { status } = await axios.delete(`/mutations/debug/${mutation.path}*`);

      if (status === 201) {
        this.mutations.splice(mutationIndex, 1);
        await this.syncMutations(this.activePage);
        if (!this.mutations.length) {
          await this.syncMutations(1);
        }
      }
    },
    focusSearchInput() {
      this.$nextTick(() => {
        if (this.$refs.searchInput) {
          this.$refs.searchInput.focus();
        }
      });
    },
    range(from, to) {
      const range = [];

      for (let i = from; i <= to; i += 1) {
        range.push(i);
      }

      return range;
    },
  },
};
</script>

<style scoped>

</style>
