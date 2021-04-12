<template>
  <scaffolding-component-container
    heading="Configure SMTP"
    :loading="loading || fetchingMutations"
  >
    <row>
      <column>
        <row>
          <column centered>
            <pg-check-box
              v-model="tls"
              centered
              label="TLS Encryption"
              @input="persistSMTPConfig"
            />
          </column>

          <column>
            <separator />
          </column>
        </row>

        <row>
          <column size="6">
            <pg-labeled-input
              v-model="host"
              label="Mail Host"
              placeholder="smtp.mailgun.org"
              @input="persistSMTPConfig"
            />
          </column>
          <column size="6">
            <pg-labeled-input
              v-model="port"
              label="Mail Port"
              placeholder="587"
              @input="persistSMTPConfig"
            />
          </column>
        </row>

        <row>
          <column size="6">
            <pg-labeled-input
              v-model="username"
              label="Mail Username"
              @input="persistSMTPConfig"
            />
          </column>
          <column size="6">
            <pg-labeled-input
              v-model="password"
              label="Mail Password"
              @input="persistSMTPConfig"
            />
          </column>
        </row>
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
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';

export default {
  name: 'SmtpMailer',
  components: {
    PgCheckBox,
    Separator,
    PgLabeledInput,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,

      tls: true,

      host: 'smtp.mailgun.org',
      port: '587',
      username: '',
      password: '',
    };
  },
  async created() {
    this.loading = true;
    await this.syncSMTPConfig();
    this.loading = false;
  },
  methods: {
    async syncSMTPConfig() {
      const { data } = await this.mutation({ path: 'mail/mailers/smtp' });

      if (data.value) {
        this.host = data.value.host || this.host;
        this.port = data.value.port || this.port;
        this.username = data.value.username || this.username;
        this.password = data.value.password || this.password;
      }
    },
    persistSMTPConfig() {
      const name = 'SMTP Configuration';
      const path = 'mail/mailers/smtp';

      const value = {
        tls: this.tls,
        host: this.host,
        port: this.port,
        username: this.username,
        password: this.password,
      };

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped></style>
