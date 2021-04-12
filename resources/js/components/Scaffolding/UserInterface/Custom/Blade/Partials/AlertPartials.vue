<template>
  <div>
    <row v-if="loading">
      <column size="4" offset="4">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column size="10" offset="1">
        <text-block info hinted>
          The alert partials are useful when you have many forms and you have to re-use the alerts.
        </text-block>
        <separator />
      </column>
      <column size="10" offset="1">
        <text-block info hinted>
          The first five buttons represent the standard bootstrap alert classes (danger, warning, info, primary and success).
          When either of the these are enabled, they'll be wrapped in the error directive. The alert name will be passed to the directive.
          The last button (C) is for custom error partial. When enabled, it will be wrapped in a conditional check that checks for the alert name
          in the session bag (@if(session(...))). For the custom error partial, the alert-info class is used.
        </text-block>
        <separator />
      </column>
      <column size="10" offset="1">
        <text-block info hinted>
          The alert partials will be placed in <span class="text-info bold">views/partials/alert</span> directory.
        </text-block>
        <separator />
      </column>
      <column size="6" offset="3" :key="alert.id" v-for="alert in alerts">
        <form-input-group compact>
          <toggle-button :value="alert.type === 'danger'"
                         off-color-class="default"
                         on-color-class="danger"
                         text="D"
                         disable-off
                         @change="onTypeChanged('danger', alert)" />
          <toggle-button :value="alert.type === 'warning'"
                         off-color-class="default"
                         on-color-class="warning"
                         text="W"
                         disable-off
                         @change="onTypeChanged('warning', alert)" />
          <toggle-button :value="alert.type === 'info'"
                         off-color-class="default"
                         on-color-class="info"
                         text="I"
                         disable-off
                         @change="onTypeChanged('info', alert)" />
          <toggle-button :value="alert.type === 'primary'"
                         off-color-class="default"
                         on-color-class="primary"
                         text="P"
                         disable-off
                         @change="onTypeChanged('primary', alert)" />
          <toggle-button :value="alert.type === 'success'"
                         off-color-class="default"
                         on-color-class="success"
                         text="S"
                         disable-off
                         @change="onTypeChanged('success', alert)" />
          <toggle-button :value="alert.type === 'custom'"
                         off-color-class="default"
                         on-color-class="green"
                         text="C"
                         disable-off
                         @change="onTypeChanged('custom', alert)" />
          <pg-input v-model="alert.name" placeholder="Alert name..." />
          <simple-button color-class="danger" @click="onDeleteAlert(alert)">
            <i class="fa fa-close"></i>
          </simple-button>
        </form-input-group>
      </column>
      <column :push5="alerts.length > 0" :centered="!alerts.length" size="6" offset="3">
        <simple-button color-class="primary" @click="onAddAlert">
          <i class="fa fa-plus"></i>
          <span v-if="!alerts.length">Add Alert</span>
        </simple-button>
      </column>
    </row>
  </div>
</template>

<script>
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import PgInput from '@/components/Forms/PgInput';
import Column from '@/components/Layout/Grid/Column';
import ToggleButton from '@/components/Forms/Buttons/ToggleButton';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import TextBlock from '@/components/Typography/Decorated/TextBlock';
import Separator from '@/components/Layout/Separator';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';

export default {
  name: 'AlertPartials',
  mixins: [mutations],
  components: {
    SimpleButton,
    Separator,
    TextBlock,
    IndeterminateProgressBar,
    PgInput,
    ToggleButton,
    FormInputGroup,
    Row,
    Column,
  },
  data() {
    return {
      loading: false,

      alerts: [],

      currentAlertType: 'error',

      changingType: false,
    };
  },
  watch: {
    alerts: {
      handler(v) {
        const payload = {
          name: 'Alert Partials',
          path: 'ui/custom/partials/alerts',
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
      const { data } = await this.mutation({ path: 'ui/custom/partials/alerts' });
      this.alerts = data.value || this.alerts;
    },

    onAddAlert() {
      this.alerts.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        name: '',
        type: this.currentAlertType,
      });
    },

    onTypeChanged(type, alert) {
      if (this.changingType) {
        return;
      }

      if (alert.type === type) {
        return;
      }

      this.changingType = true;

      alert.type = type;

      this.$nextTick(() => {
        this.changingType = false;
      });
    },

    onDeleteAlert(alert) {
      const alertIndex = this.alerts.findIndex((a) => a.id === alert.id);

      if (alertIndex > -1) {
        this.alerts.splice(alertIndex, 1);
      }
    },
  },
};
</script>

<style scoped>

</style>
