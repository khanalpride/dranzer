<template>
  <scaffolding-component-container heading="Configure Nginx" :loading="loading || fetchingMutations">
    <row>
      <column size="6" offset="3">
        <p class="text-primary">
          <i class="fa fa-info"></i>
          The configuration below works for most Laravel installations.
          If you require a more complex configuration, head over to
          <a href="https://www.digitalocean.com/community/tools/nginx"
             class="text-complete link"
             target="_blank">
            Digital Ocean's Nginx Tool
          </a>
          to configure your server.
        </p>
      </column>

      <column push15 size="6" offset="3">
        <pg-labeled-input v-model="config.root" label="Server Root"/>
      </column>

      <column>
        <separator/>
      </column>

      <column size="3" offset="2">
        <form-input-title title="Client Max Body Size (MB)" />
        <el-input-number style="width: 100% !important;" v-model="config.maxBodySize" :min="32" :max="99999" />
      </column>

      <column size="5">
        <form-input-title title="Server Names" />
        <simple-select filterable
                       allow-create
                       multiple
                       collapse-tags
                       full-width
                       :entities="config.serverNames"
                       :value="serverNamesOnly"
                       @change="onServerNamesUpdated">
          <template slot-scope="{ entity }">
            <el-option :key="entity.id"
                       :label="entity.name"
                       :value="entity.name" />
          </template>
        </simple-select>
      </column>

      <column size="8" offset="2">
        <row>
          <column>
            <separator/>
          </column>

          <column centered>
            <form-input-title title="PHP FPM Version" />
            <simple-select v-model="config.phpFPMVersion" :entities="phpFPMVersions">
              <template slot-scope="{ entity }">
                <el-option :key="entity.value"
                           :label="entity.label"
                           :value="entity.value" />
              </template>
            </simple-select>
          </column>
        </row>
        <row>
          <column>
            <separator/>
          </column>

          <column centered>
            <pg-check-box no-margin centered v-model="config.copyConfig" label="Copy Config To Project Root" />
          </column>
        </row>

        <row>
          <column>
            <separator/>
          </column>

          <column>
            <pre v-html="configString"/>
          </column>
        </row>

      </column>
    </row>
  </scaffolding-component-container>
</template>

<!--suppress SpellCheckingInspection -->
<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import Separator from '@/components/Layout/Separator';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import SimpleSelect from '@/components/Select/SimpleSelect';

export default {
  name: 'NginxConfigurationContainer',
  mixins: [asyncImports, mutations],
  components: {
    SimpleSelect,
    FormInputTitle,
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

      config: {
        listeningPort: 80,

        root: '/var/www/html/app/public',

        serverNames: [],

        phpFPMVersion: 'php8.0-fpm',

        copyConfig: false,

        maxBodySize: 128,
      },

      phpFPMVersions: [
        {
          value: 'php7.1-fpm',
          label: 'PHP FPM 7.1',
        },
        {
          value: 'php7.2-fpm',
          label: 'PHP FPM 7.2',
        },
        {
          value: 'php7.3-fpm',
          label: 'PHP FPM 7.3',
        },
        {
          value: 'php7.4-fpm',
          label: 'PHP FPM 7.4',
        },
        {
          value: 'php8.0-fpm',
          label: 'PHP FPM 8.0',
        },
      ],
    };
  },
  computed: {
    serverNamesOnly() {
      return this.config.serverNames.map((s) => s.name);
    },

    configString() {
      return `
<span class="text-complete">server</span> {
    <span class="text-complete">listen</span> <span class="text-success">${this.config.listeningPort}</span> default_server;

    <span class="text-complete">server_name</span> ${this.config.serverNames.filter((s) => s.name.trim() !== '').map((s) => s.name).join(', ')};

    <span class="text-complete">root</span> ${this.config.root};

    <span class="text-complete">client_max_body_size</span> ${this.config.maxBodySize}M;

    <span class="text-complete">index</span> index.php;

    <span class="text-complete">charset</span> utf-8;

    <span class="text-complete">location</span> / {
        <span class="text-complete">try_files</span> $uri $uri/ /index.php?$query_string;
    }

    <span class="text-complete">location</span> = /favicon.ico { access_log off; log_not_found off; }
    <span class="text-complete">location</span> = /robots.txt  { access_log off; log_not_found off; }

    <span class="text-complete">error_page</span> <span class="text-success">404</span> /index.php;

    <span class="text-complete">location</span> ~ \\.php$ {
        <span class="text-complete">include</span> snippets/fastcgi-php.conf;
        <span class="text-complete">fastcgi_pass</span> unix:/var/run/php/${this.config.phpFPMVersion}.sock;
    }

    <span class="text-complete">location</span> ~ /\\.(?!well-known).* {
        <span class="text-danger">deny</span> all;
    }
}`;
    },
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

    if (!this.config.serverNames.length) {
      this.addServerName('example.com');
    }
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'deployment/nginx' });
      this.config = data.value || this.config;
    },

    persist() {
      const name = 'Nginx Config';
      const path = 'deployment/nginx';
      const value = this.config;

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },

    addServerName(name = '') {
      this.config.serverNames.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        name,
      });
    },

    onServerNamesUpdated(names) {
      const missing = names.filter((n) => !this.config.serverNames.find((s) => s.name === n));

      missing.forEach((m) => this.addServerName(m));

      this.config.serverNames = this.config.serverNames.filter((s) => names.includes(s.name));
    },
  },
};
</script>

<style scoped>

</style>
