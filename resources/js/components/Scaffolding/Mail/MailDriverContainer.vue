<template>
  <scaffolding-component-container
    heading="Configure Mail"
    :loading="loading || fetchingMutations"
  >
    <row>
      <column centered>
        <p class="text-primary no-margin">
          Mail Driver
        </p>
      </column>

      <column>
        <separator />
      </column>

      <column centered>
        <el-radio-group
          v-model="driver"
          @change="persistDriver"
        >
          <el-radio
            v-for="d in drivers"
            :key="d.id"
            :label="d.name"
          >
            {{ d.label }}
          </el-radio>
        </el-radio-group>
      </column>

      <column
        v-if="driver === 'smtp'"
        size="10"
        offset="1"
        push10
      >
        <smtp-mailer />
      </column>

      <column
        size="10"
        offset="1"
        push10
      >
        <content-card heading="Sender Configuration">
          <row>
            <column size="6">
              <pg-labeled-input
                v-model="senderName"
                label="Sender Name"
                placeholder="John Doe"
                @input="persistSenderConfig"
              />
            </column>
            <column size="6">
              <pg-labeled-input
                v-model="senderEmail"
                label="Sender Email"
                placeholder="john.doe@example.com"
                @input="persistSenderConfig"
              />
            </column>
          </row>
        </content-card>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import ContentCard from '@/components/Cards/ContentCard';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import SmtpMailer from '@/components/Scaffolding/Mail/Mailers/SmtpMailer';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';

export default {
  name: 'MailDriverContainer',
  components: {
    PgLabeledInput,
    Separator,
    SmtpMailer,
    ScaffoldingComponentContainer,
    Column,
    ContentCard,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,

      senderName: 'John Doe',
      senderEmail: 'john.doe@example.com',

      driver: 'smtp',

      drivers: [
        {
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          label: 'SMTP',
          name: 'smtp',
        },
        {
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          label: 'SES',
          name: 'ses',
        },
        {
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          label: 'Mailgun',
          name: 'mailgun',
        },
        {
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          label: 'Postmark',
          name: 'postmark',
        },
        {
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          label: 'SendMail',
          name: 'sendmail',
        },
        {
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          label: 'Log',
          name: 'log',
        },
        {
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          label: 'Array',
          name: 'array',
        },
      ],
    };
  },
  async created() {
    this.loading = true;
    await this.syncDriver();
    await this.syncSenderConfig();
    this.loading = false;
  },
  methods: {
    async syncDriver() {
      const { data } = await this.mutation({ path: 'mail/driver' });
      this.driver = data.value || this.driver;
    },
    persistDriver() {
      const name = 'Mail Driver';
      const path = 'mail/driver';
      const value = this.driver;

      this.mutate(value, name, path);
    },

    async syncSenderConfig() {
      const { data } = await this.mutation({
        path: 'mail/sender/config',
      });
      if (data.value) {
        this.senderName = data.value.senderName || this.senderName;
        this.senderEmail = data.value.senderEmail || this.senderEmail;
      }
    },

    persistSenderConfig() {
      const name = 'Mail Sender Configuration';
      const path = 'mail/sender/config';
      const value = {
        senderName: this.senderName,
        senderEmail: this.senderEmail,
      };

      this.mutate(value, name, path);
    },
  },
};
</script>

<style scoped></style>
