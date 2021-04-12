<template>
  <scaffolding-component-container heading="Configure Notifications" :loading="loading || fetchingMutations">
    <row>
      <column>
        <pg-input v-model="definition" placeholder="Enter notifications definition..." @keyup.native.enter="onCreateNotificationsFromInput"/>
      </column>

      <column v-if="notifications.length">
        <row>
          <column size="8" push5>
            <pg-input v-model="slackWebHook" placeholder="Slack webhook..." @input="persistSlackWebhook" />
          </column>
          <column size="4" push5>
            <pg-input v-model="nexmoRecipient" placeholder="SMS recipient number..." @input="persistNexmoRecipient" />
          </column>
          <column push10>
            <tabs-manager ref="notificationTabs"
                          :tabs="tabs"
                          path="config/notifications/tabs/active"
                          @remove="onDeleteNotification($event)">
              <template :slot="notification.id" v-for="notification in notifications">
                <notification :persisted="notification"
                              :blueprints="blueprints"
                              :mailables="mailables"
                              :key="notification.id"
                              @updated="onNotificationUpdated($event, notification)" />
              </template>
            </tabs-manager>
          </column>
        </row>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import mutations from '@/mixins/mutations';
import Row from '@/components/Layout/Grid/Row';
import PgInput from '@/components/Forms/PgInput';
import asyncImports from '@/mixins/async_imports';
import Column from '@/components/Layout/Grid/Column';
import TabsManager from '@/components/Tabs/TabsManager';
import sharedMutations from '@/mixins/shared_mutations';
import Notification from '@/components/Scaffolding/Notifications/Notification';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';

export default {
  name: 'NotificationsManager',
  mixins: [asyncImports, mutations, sharedMutations],
  components: {
    TabsManager,
    Notification,
    PgInput,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  data() {
    return {
      loading: false,

      definition: '',

      notifications: [],

      mailables: [],

      blueprints: [],

      slackWebHook: '',

      nexmoRecipient: '',
    };
  },
  computed: {
    tabs() {
      return this.notifications.map((n) => ({ id: n.id, label: n.name, removable: true }));
    },
  },
  async created() {
    this.loading = true;
    await this.syncNotifications();
    await this.syncMailables();
    await this.syncSlackWebhook();
    await this.syncNexmoRecipient();
    await this.assignBlueprints();
    this.loading = false;
  },
  methods: {
    async syncNotifications() {
      const { data } = await this.mutation({ path: 'notifications/', like: true, refresh: true });
      this.notifications = data.value ? data.value.map((v) => v.value) : this.notifications;
    },

    async syncMailables() {
      const { data } = await this.mutation({ path: 'mail/mailables/', like: true, refresh: true });
      this.mailables = data.value ? data.value.map((v) => v.value) : this.mailables;
    },

    async syncSlackWebhook() {
      const { data } = await this.mutation({ path: 'config/notifications/slack/webhook' });
      this.slackWebHook = data.value || this.slackWebHook;
    },

    async syncNexmoRecipient() {
      const { data } = await this.mutation({ path: 'config/notifications/nexmo/recipient' });
      this.nexmoRecipient = data.value || this.nexmoRecipient;
    },

    persistNotification(notification) {
      const name = 'Notification';
      const path = `notifications/${notification.id}`;

      const payload = {
        name,
        path,
        value: notification,
      };

      this.mutate(payload);
    },

    persistSlackWebhook() {
      const payload = {
        name: 'Slack Webhook',
        path: 'config/notifications/slack/webhook',
        value: this.slackWebHook,
      };

      this.mutate(payload);
    },

    persistNexmoRecipient() {
      const payload = {
        name: 'Nexmo Recipient',
        path: 'config/notifications/nexmo/recipient',
        value: this.nexmoRecipient,
      };

      this.mutate(payload);
    },

    onCreateNotificationsFromInput() {
      const input = this.definition.trim();

      if (input === '') {
        return;
      }

      const notifications = input.split(',');

      const addedNotifications = [];

      notifications.forEach((notification) => {
        const notificationName = notification.replace(/\s/g, '').trim();

        if (!/^[a-zA-Z0-9]+$/g.test(notificationName)) {
          return false;
        }

        if (!this.notifications.find((n) => n.name.toLowerCase().trim() === notificationName.toLowerCase().trim())) {
          const newNotification = {
            id: `N${Math.round(Math.random() * Number.MAX_SAFE_INTEGER)}`,
            name: this.str.humanize(notificationName),
            via: {
              mail: {
                enabled: true,
                mailables: [],
              },
              slack: {
                enabled: false,
                content: '',
                from: {
                  username: '',
                  icon: '',
                },
                channel: '',
              },
              sms: {
                enabled: false,
                content: '',
                from: '',
                to: '',
              },
            },
            typeHint: [],
          };

          this.notifications.push(newNotification);

          addedNotifications.push(newNotification);
        }

        return true;
      });

      if (addedNotifications.length) {
        this.mutate({
          name: 'Notifications',
          bulk: true,
          value: addedNotifications.map((n) => ({
            name: 'Notification',
            path: `notifications/${n.id}`,
            value: n,
          })),
        });
      }

      this.definition = '';
    },

    async onDeleteNotification(notificationId) {
      const nIndex = this.notifications.findIndex((n) => n.id === notificationId);
      if (nIndex > -1) {
        const { status } = await this.deleteMutation(`notifications/${notificationId}`);
        if (status === 201 || status === 404) {
          this.notifications.splice(nIndex, 1);

          this.$nextTick(() => {
            if (this.$refs.notificationTabs) {
              this.$refs.notificationTabs.activateTabByIndex(this.notifications.length - 1);
            }
          });
        }
      }
    },

    onNotificationUpdated(updatedNotification, notification) {
      const notificationIndex = this.notifications.findIndex((m) => m.id === notification.id);

      if (notificationIndex > -1) {
        this.notifications[notificationIndex] = updatedNotification;
        this.persistNotification(this.notifications[notificationIndex]);
      }
    },
  },
};
</script>

<style scoped>

</style>
