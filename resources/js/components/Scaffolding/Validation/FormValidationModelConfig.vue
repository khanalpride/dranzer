<template>
  <div>
    <row v-if="loading">
      <column size="4" offset="4">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column centered>
        <pg-check-box :value="config.auth === 'user'" centered no-margin label="Authorize if user is logged in"
                      @change="onAuthorizationModeChanged($event, 'user')" />
        <pg-check-box :value="config.auth === true" centered no-margin label="Authorized"
                      @change="onAuthorizationModeChanged($event, true)" />
        <pg-check-box :value="config.auth === false" centered no-margin label="Not Authorized"
                      @change="onAuthorizationModeChanged($event, false)" />
      </column>
    </row>
  </div>
</template>

<script>
import mutations from '@/mixins/mutations';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'FormValidationModelConfig',
  mixins: [mutations],
  props: {
    modelId: {},
  },
  components: {
    IndeterminateProgressBar, PgCheckBox, Row, Column,
  },
  data() {
    return {
      loading: false,

      config: {
        auth: 'user',
      },
    };
  },
  watch: {
    config: {
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
      const { data } = await this.mutation({ path: `validation/config/${this.modelId}` });
      this.config = data.value || this.config;
    },
    persist() {
      const payload = {
        name: 'Form Validation Config',
        path: `validation/config/${this.modelId}`,
        value: this.config,
      };

      this.mutate(payload);
    },
    onAuthorizationModeChanged(active, mode) {
      this.config.auth = mode;
    },
  },
};
</script>

<style scoped>

</style>
