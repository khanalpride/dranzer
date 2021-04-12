<template>
  <div>
    <row v-if="loading">
      <column size="4" offset="4">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column>
        <row>
          <column>
            <basic-content-section heading="Type Hint">
              <row>
                <column centered v-if="visibleModels.length">
                  <form-input-title title="Models" no-bottom-padding />
                  <separator />
                  <pg-check-box no-margin
                                centered
                                :value="isTypeHinted(model.id)"
                                :key="model.id"
                                :label="model.modelName"
                                v-for="model in visibleModels"
                                @change="onTypeHintedEntityChanged($event, model.id, model.modelName, 'model')" />
                </column>
                <column centered v-if="mailables.length">
                  <separator />
                  <form-input-title title="Mailables" no-bottom-padding />
                  <separator />
                  <pg-check-box no-margin
                                centered
                                :value="isTypeHinted(mailable.id)"
                                :key="mailable.id"
                                :label="mailable.name"
                                v-for="mailable in mailables"
                                @change="onTypeHintedEntityChanged($event, mailable.id, mailable.name, 'mailable')"/>
                </column>
                <column centered v-if="notifications.length">
                  <separator />
                  <form-input-title title="Notifications" no-bottom-padding />
                  <separator />
                  <pg-check-box no-margin
                                centered
                                :value="isTypeHinted(notification.id)"
                                :key="notification.id"
                                :label="notification.name"
                                v-for="notification in notifications"
                                @change="onTypeHintedEntityChanged($event, notification.id, notification.name, 'notification')" />
                </column>
              </row>
            </basic-content-section>

            <basic-content-section heading="Uniqueness" prepend-separator>
              <row>
                <column centered>
                  <pg-check-box v-model="job.unique"
                                no-margin
                                centered
                                label="Unique"
                                @change="onUniqueToggled" />
                  <pg-check-box v-model="job.uniqueUntilProcessing"
                                no-margin
                                centered
                                label="Unique Until Processing"
                                @change="onUniqueUntilProcessingToggled" />
                </column>
                <column>
                  <separator />
                </column>
                <column size="8" offset="2" v-if="job.unique || job.uniqueUntilProcessing">
                  <row>
                    <column centered>
                      <pg-check-box v-model="job.overrideUniqueDuration" no-margin centered label="Override Unique Duration" />
                      <separator />
                    </column>
                    <column size="4" offset="2">
                      <form-input-title title="Unique Duration (Seconds)" />
                      <el-input-number :disabled="!job.overrideUniqueDuration"
                                       v-model="job.uniqueFor"
                                       class="full-width"
                                       :min="1"
                                       :max="999999" />
                    </column>
                    <column size="4">
                      <form-input-title title="Unique Via (Cache Driver)" />
                      <pg-input class="input-max-height" placeholder="e.g. redis" v-model="job.uniqueVia" />
                    </column>
                  </row>
                </column>
                <column v-if="job.unique || job.uniqueUntilProcessing">
                  <separator />
                </column>
              </row>
            </basic-content-section>

            <basic-content-section heading="Middleware">
              <row>
                <column size="4" offset="4">
                  <pg-input v-model="job.middleware" placeholder="Middleware name (empty to disable)..." />
                </column>
              </row>
            </basic-content-section>
          </column>
        </row>
      </column>
    </row>
  </div>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import entity from '@/mixins/entity';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Separator from '@/components/Layout/Separator';
import BasicContentSection from '@/components/Content/BasicContentSection';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import PgInput from '@/components/Forms/PgInput';

export default {
  name: 'Job',
  props: {
    persisted: {},
    blueprints: Array,
    mailables: Array,
    notifications: Array,
  },
  mixins: [asyncImports, mutations, entity],
  components: {
    PgInput,
    FormInputTitle,
    BasicContentSection,
    Separator,
    PgCheckBox,
    IndeterminateProgressBar,
    Row,
    Column,
  },
  data() {
    return {
      loading: false,
      ready: false,

      job: {
        name: (this.persisted || {}).name || '',
        unique: false,
        uniqueUntilProcessing: false,
        overrideUniqueDuration: false,
        uniqueFor: 120,
        uniqueVia: null,
        middleware: null,
        typeHint: [],
      },
    };
  },
  computed: {
    visibleModels() {
      return this.blueprints.filter((s) => s.visible !== false);
    },
  },
  watch: {
    job: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        const payload = {
          name: 'Job Config',
          path: `queues/jobs/${this.persisted.id}`,
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

    this.$nextTick(() => {
      this.ready = true;
    });
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: `queues/jobs/${this.persisted.id}` });
      this.job = this.syncEntity((data.value || {}), this.job);
    },

    isTypeHinted(entityId) {
      return this.job.typeHint.find((t) => t.id === entityId) !== undefined;
    },

    onUniqueToggled(active) {
      if (active) {
        this.job.uniqueUntilProcessing = false;
      }
    },

    onUniqueUntilProcessingToggled(active) {
      if (active) {
        this.job.unique = false;
      }
    },

    onTypeHintedEntityChanged(active, entityId, entityName, type) {
      const typeHinted = this.job.typeHint;

      const typeHintedModelIndex = typeHinted.findIndex((t) => t.id === entityId && t.type === type);

      if (active) {
        if (typeHintedModelIndex < 0) {
          typeHinted.push({ id: entityId, name: entityName, type });
        } else {
          typeHinted[typeHintedModelIndex] = { id: entityId, name: entityName, type };
        }
      } else if (typeHintedModelIndex > -1) {
        typeHinted.splice(typeHintedModelIndex, 1);
      }
    },
  },
};
</script>

<style scoped>

</style>
