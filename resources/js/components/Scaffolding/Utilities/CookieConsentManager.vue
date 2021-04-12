<template>
  <scaffolding-component-container heading="Manage Laravel Cookies Consent" :loading="loading || fetchingMutations">
    <row>
      <column>
        <blockquote class="text-info hint-text no-margin">
          All sites owned by EU citizens or targeted towards EU citizens must comply with a crazy EU law.
          This law requires a dialog to be displayed to inform the users of your websites how cookies are being used.
          You can read more info on the legislation
          <a href="https://wikis.ec.europa.eu/display/WEBGUIDE/04.+Cookies" target="_blank">
            <i class="fa fa-external-link small"></i> on the site of the European Commission</a><span>.</span>
        </blockquote>
      </column>
      <column centered>
        <separator />
        <pg-check-box no-margin centered v-model="config.install" label="Install Laravel Cookies Consent"/>
      </column>
      <column centered v-if="config.install">
        <separator/>
        <pg-check-box centered
                      no-margin
                      v-model="config.middleware"
                      label="As Middleware"
                      v-tooltip.15 content="
                      Instead of including [c]cookieConsent::index[/c] in all your views,
                      this will enable [c]Spatie\CookieConsent\CookieConsentMiddleware[/c]
                      middleware which automatically adds [c]cookieConsent::index[/c] to the content of your response
                      right before the closing body tag
                      "/>
        <pg-check-box no-margin centered v-model="config.publishLang" label="Publish Language Files"/>
        <pg-check-box no-margin centered v-model="config.publishViews" label="Publish Views"/>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Separator from '@/components/Layout/Separator';

export default {
  name: 'CookieConsentManager',
  mixins: [asyncImports, mutations],
  components: {
    Separator,
    PgCheckBox,
    Row,
    Column,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,
      ready: false,

      config: {
        install: false,
        middleware: true,
        publishLang: false,
        publishViews: false,
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

    this.$nextTick(() => {
      this.ready = true;
    });
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'compliance/cc/install' });
      this.config = data.value || this.config;
    },
    persist() {
      if (!this.ready) {
        return;
      }

      const payload = {
        name: 'Cookie Consent Package',
        path: 'compliance/cc/install',
        value: this.config,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
