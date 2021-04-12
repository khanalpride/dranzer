<template>
  <row>
    <column centered v-if="visibleModels.length">
      <basic-content-section heading="Type Hint">
        <row>
          <column>
            <pg-check-box no-margin
                          centered
                          :value="isTypeHinted(model)"
                          :label="str.ellipse(model.modelName, 15)"
                          :key="model.id"
                          v-for="model in visibleModels"
                          @change="onTypeHintedModelsChanged($event, model)" />
          </column>
        </row>
      </basic-content-section>
    </column>

    <column centered>
      <basic-content-section heading="Channels" prepend-separator>
        <pg-check-box centered v-model="notification.via.mail.enabled" no-margin label="Mail"/>
        <pg-check-box centered v-model="notification.via.slack.enabled" no-margin label="Slack"/>
        <pg-check-box centered v-model="notification.via.sms.enabled" no-margin label="SMS"/>
      </basic-content-section>
    </column>

    <column>
      <row>
        <column v-if="notification.via.mail.enabled">
          <row>
            <column centered>
              <basic-content-section heading="Mailables" prepend-separator>
                <pg-check-box no-margin
                              centered
                              :value="isMailableSelected(mailable)"
                              :label="str.ellipse(mailable.name, 15)"
                              :key="mailable.id"
                              v-for="mailable in mailables"
                              @change="onMailableToggled($event, mailable)" />
              </basic-content-section>
            </column>
          </row>
        </column>
        <column v-if="notification.via.slack.enabled">
          <row>
            <column>
              <basic-content-section heading="Slack" prepend-separator>
                <row>
                  <column size="10" offset="1">
                    <row>
                      <column>
                        <form-input-title :centered="false" title="Notification Content" />
                        <pg-input v-model="notification.via.slack.content" />
                      </column>
                      <column push5 size="8">
                        <form-input-title :centered="false" title="From (Username / Icon (Can be a remote image))" />
                        <form-input-group>
                          <pg-input v-model="notification.via.slack.from.username" placeholder="Username (Defaults to App)" />
                          <pg-input v-model="notification.via.slack.from.icon" placeholder="Icon" />
                        </form-input-group>
                      </column>
                      <column push5 size="4">
                        <form-input-title :centered="false" title="Channel" />
                        <pg-input v-model="notification.via.slack.channel" placeholder="general" />
                      </column>
                    </row>
                  </column>
                </row>
              </basic-content-section>
            </column>
          </row>
        </column>
        <column v-if="notification.via.sms.enabled">
          <row>
            <column>
              <basic-content-section heading="SMS" prepend-separator>
                <row>
                  <column size="10" offset="1">
                    <row>
                      <column>
                        <form-input-title :centered="false" title="Notification Content" />
                        <pg-input v-model="notification.via.sms.content" />
                      </column>
                      <column push5 size="4" offset="4">
                        <form-input-title :centered="false" title="From" />
                        <pg-input v-model="notification.via.sms.from" />
                      </column>
                    </row>
                  </column>
                </row>
              </basic-content-section>
            </column>
          </row>
        </column>
      </row>
    </column>
  </row>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import entity from '@/mixins/entity';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import PgInput from '@/components/Forms/PgInput';
import BasicContentSection from '@/components/Content/BasicContentSection';

export default {
  name: 'Notification',
  props: {
    persisted: {},
    blueprints: Array,
    mailables: Array,
  },
  mixins: [asyncImports, mutations, entity],
  components: {
    BasicContentSection,
    PgInput,
    FormInputGroup,
    FormInputTitle,
    PgCheckBox,
    Row,
    Column,
  },
  data() {
    return {
      loading: false,

      notification: this.persisted || {},
    };
  },
  computed: {
    visibleModels() {
      return this.blueprints.filter((s) => s.visible !== false);
    },
  },
  watch: {
    notification: {
      handler(v) {
        this.$emit('updated', v);
      },
      deep: true,
    },
    persisted: {
      handler(v) {
        this.notification = v;
      },
    },
  },
  methods: {
    isTypeHinted(model) {
      return this.notification.typeHint.find((t) => t.id === model.id) !== undefined;
    },

    isMailableSelected(mailable) {
      return this.notification.via.mail.mailables.find((m) => m.id === mailable.id) !== undefined;
    },

    onNotificationStateToggled(active) {
      if (!active) {
        this.$emit('delete');
      }
    },

    onTypeHintedModelsChanged(active, model) {
      const typeHintedModelIndex = this.notification.typeHint.findIndex((t) => t.id === model.id);

      if (active) {
        if (typeHintedModelIndex < 0) {
          this.notification.typeHint.push({ id: model.id, name: model.modelName });
        } else {
          this.notification.typeHint[typeHintedModelIndex] = { id: model.id, name: model.modelName };
        }
      } else if (typeHintedModelIndex > -1) {
        this.notification.typeHint.splice(typeHintedModelIndex, 1);
      }
    },

    onMailableToggled(active, mailable) {
      const { mailables } = this.notification.via.mail;

      const mailableIndex = mailables.findIndex((m) => m.id === mailable.id);

      if (active) {
        if (mailableIndex < 0) {
          mailables.push({ id: mailable.id, name: mailable.name });
        } else {
          this.notification.via.mail.mailables[mailableIndex] = { id: mailable.id, name: mailable.name };
        }

        this.notification.via.mail.mailables = this.notification.via.mail.mailables.filter((m) => m.id === mailable.id);
      } else if (mailableIndex > -1) {
        mailables.splice(mailableIndex, 1);
      }
    },
  },
};
</script>

<style scoped>

</style>
