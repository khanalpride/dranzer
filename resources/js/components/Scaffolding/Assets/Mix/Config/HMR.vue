<template>
    <content-card heading="HMR Configuration">
        <div v-if="fetchingMutations || loading">
            <row>
                <column size="4" offset="4">
                    <p class="text-center">Restoring Configuration...</p>
                    <indeterminate-progress-bar />
                </column>
            </row>
        </div>

        <div v-else>
            <row>
                <column size="8" offset="2">
                    <row>
                        <column centered>
                            <pg-check-box centered
                                          label="Enable HMR"
                                          v-model="config.enabled" />
                            <pg-check-box centered
                                          label="Use HTTPS"
                                          v-model="config.https"
                                          :disabled="!config.enabled" />
                        </column>

                        <column push20>
                            <row>
                                <column size="6">
                                  <pg-labeled-input :disabled="!config.enabled"
                                                    :class="{'transparent-container': !config.enabled}"
                                                    label="Host"
                                                    v-model="config.host"/>
                                </column>
                                <column size="6">
                                  <pg-labeled-input :disabled="!config.enabled"
                                                    :class="{'transparent-container': !config.enabled}"
                                                    label="Port"
                                                    v-model="config.port"/>
                                </column>
                            </row>
                        </column>
                    </row>
                </column>
            </row>
        </div>
    </content-card>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import ContentCard from '@/components/Cards/ContentCard';
import Column from '@/components/Layout/Grid/Column';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'HMR',
  mixins: [asyncImports, mutations],
  components: {
    PgLabeledInput, PgCheckBox, IndeterminateProgressBar, Column, ContentCard, Row,
  },
  data() {
    return {
      loading: false,

      config: {
        enabled: false,
        https: true,

        host: '',
        port: '8080',
      },
    };
  },
  watch: {
    config: {
      handler(v) {
        const name = 'HMR Options';
        const path = 'assets/mix/hmr';

        const payload = {
          name,
          path,
          value: v,
        };

        this.mutate(payload);
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
      const { data } = await this.mutation({ path: 'assets/mix/hmr' });
      this.config = data.value || this.config;
    },
  },
};
</script>

<style scoped>

</style>
