<template>
  <scaffolding-component-container heading="Configure Supervisor" :loading="loading || fetchingMutations">
    <row>
      <column size="6" offset="3">
        <p class="text-primary no-margin">
          <i class="fa fa-info"></i>
          The configuration below works for basic installations.
          If you require an advanced configuration, head over to
          <a href="http://supervisord.org/configuration.html"
             class="text-complete link"
             target="_blank">
            Supervisord Configuration Guide
          </a>
          to create a custom configuration.
        </p>
      </column>

      <column>
        <separator/>
      </column>

      <column size="10" offset="1">
        <row>
          <column size="4">
            <pg-labeled-input v-model="artisanPath" label="Artisan Path" placeholder="/var/www/html/app/artisan"
                              @input="persist"/>
          </column>

          <column size="4">
            <pg-labeled-input v-model="logFilePath" label="Log File Path"
                              placeholder="/var/www/html/app/storage/logs/horizon.log" @input="persist"/>
          </column>

          <column size="4">
            <pg-labeled-input v-model="username" label="Username" placeholder="forge" @input="persist"/>
          </column>
        </row>
      </column>

      <column>
        <separator/>
      </column>

      <column centered>
        <pg-check-box no-margin centered v-model="copyConfig" label="Copy Config To Project Root" @change="persist"/>
      </column>

      <column>
        <separator/>
      </column>

      <column size="10" offset="1">
        <p class="text-info hint-text p-r-50">
          <i class="fa fa-info"></i>
          Place the configuration below at the bottom of your
          <strong>supervisord.conf</strong>
          file which is located inside
          <strong>/etc/</strong>.
          If you can't find the config file,
          have a <a href="http://supervisord.org/configuration.html" target="_blank"><strong>look at the docs</strong></a>
          to figure out where the config file might be for your supervisor installation.
        </p>
        <pre v-html="config"/>
        <p class="text-info hint-text">
          <i class="fa fa-info"></i>
          Once you've updated the configuration file, run the following command from your terminal to load the updated configuration:
        </p>
        <p>
          <code>sudo supervisorctl reload</code>
        </p>
      </column>

    </row>
  </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import Separator from '@/components/Layout/Separator';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';

export default {
  name: 'SupervisorContainer',
  mixins: [asyncImports, mutations],
  components: {
    PgCheckBox,
    Separator,
    PgLabeledInput,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  data() {
    return {
      loading: false,

      artisanPath: '/var/www/html/app/artisan',

      logFilePath: '/var/www/html/app/storage/logs/horizon.log',

      username: 'forge',

      copyConfig: false,
    };
  },
  computed: {
    config() {
      const { artisanPath } = this;
      const { logFilePath } = this;
      const username = this.username.replace('forge', '<span class="text-danger">forge</span>');
      return `
<span class="text-complete">[program:horizon]</span>
<span class="text-complete">process_name</span>=%(program_name)s
<span class="text-complete">command</span>=<span class="text-green">php</span> ${artisanPath} <span class="text-green">horizon</span>
<span class="text-complete">autostart</span>=<span class="text-primary">true</span>
<span class="text-complete">autorestart</span>=<span class="text-primary">true</span>
<span class="text-complete">redirect_stderr</span>=<span class="text-primary">true</span>
<span class="text-complete">user</span>=${username}
<span class="text-complete">stdout_logfile</span>=${logFilePath}
<span class="text-complete">stopwaitsecs</span>=<span class="text-primary">3600</span>
`;
    },
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'queues/supervisor' });
      if (data.value) {
        const { value } = data;
        this.artisanPath = value.artisanPath !== undefined ? value.artisanPath : this.artisanPath;
        this.logFilePath = value.logFilePath !== undefined ? value.logFilePath : this.logFilePath;
        this.username = value.username !== undefined ? value.username : this.username;
        this.copyConfig = value.copyConfig !== undefined ? value.copyConfig : this.copyConfig;
      }
    },

    persist() {
      const name = 'Supervisor Config';
      const path = 'queues/supervisor';
      const value = {
        artisanPath: this.artisanPath,
        logFilePath: this.logFilePath,
        username: this.username,
        copyConfig: this.copyConfig,
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

<style scoped>

</style>
