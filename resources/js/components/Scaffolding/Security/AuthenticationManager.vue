<template>
  <scaffolding-component-container heading="Manage Authentication" :loading="loading || fetchingMutations">
    <row>
     <column centered>
       <pg-check-box v-model="authEnabled" no-margin centered label="Enable Authentication" />
     </column>
      <column v-if="authEnabled">
        <separator />
        <p class="text-center">
          <external-link url="https://laravel.com/docs/8.x/authentication#introduction">
            Authentication Overview
          </external-link>
          <external-link url="https://github.com/laravel/ui#introduction">
            Laravel UI Docs
          </external-link>
          <external-link url="https://laravel.com/docs/8.x/starter-kits#laravel-breeze">
            Breeze Docs
          </external-link>
          <external-link url="https://laravel.com/docs/8.x/fortify">
            Fortify Docs
          </external-link>
        </p>
        <separator />
        <row>
          <column centered>
            <pg-check-box v-model="modules.ui"
                          no-margin
                          centered
                          label="Using Laravel UI"
                          @change="onUIStateChanged" />
            <pg-check-box v-model="modules.breeze"
                          no-margin
                          centered
                          label="Using Laravel Breeze"
                          @change="onBreezeStateChanged" />
            <pg-check-box v-model="modules.fortify"
                          no-margin
                          centered
                          label="Using Laravel Fortify"
                          @change="onFortifyStateChanged" />
          </column>
        </row>
        <row>
          <column>
            <basic-content-section heading="Laravel UI" prepend-separator v-if="modules.ui">
              <row>
                <column centered>
                  <p class="text-info hint-text no-margin">
                    <i class="fa fa-exclamation-triangle"></i> <app-name /> will try to use the latest dependencies which may cause
                    conflicts with the dependencies defined in the
                    <a href="https://github.com/laravel/ui" target="_blank">
                      laravel/ui
                    </a> package.
                  </p>
                  <separator />
                </column>
                <column centered>
                  <pg-check-box v-model="ui.registration" no-margin centered label="Enable Registration" />
                  <pg-check-box v-model="ui.verify" no-margin centered label="Enable Email Verification" />
                  <pg-check-box v-model="ui.resets" no-margin centered label="Enable Password Resets" />
                </column>
                <column centered v-if="ui.verify">
                  <separator />
                  <p class="text-info hint-text no-margin">
                    <i class="fa fa-exclamation-triangle"></i>
                    You will have to <strong class="text-primary">add the verified middleware yourself</strong>
                    to the routes that can only be accessed by verified users.
                  </p>
                </column>
                <column>
                  <basic-content-section heading="Frontend Library" heading-color="text-complete" prepend-separator>
                    <row>
                      <column centered>
                        <el-radio v-model="ui.library" label="vue">Vue</el-radio>
                        <el-radio v-model="ui.library" label="react">React</el-radio>
                        <el-radio v-model="ui.library" label="bootstrap">Bootstrap</el-radio>
                      </column>
                    </row>
                  </basic-content-section>
                </column>
              </row>
            </basic-content-section>

            <basic-content-section heading="Laravel Breeze" prepend-separator v-if="modules.breeze">
              <row>
                <column centered>
                  <p class="text-info hint-text no-margin">
                    <i class="fa fa-exclamation-triangle"></i>
                    If you've configured tailwindcss in <app-name />, the configuration will be merged with the one required by Laravel Breeze.
                  </p>
                  <separator />
                </column>
                <column centered>
                  <pg-check-box v-model="breeze.registration"
                                no-margin
                                centered
                                label="Enable Registration" @change="onBreezeRegistrationToggled" />
                  <pg-check-box v-model="breeze.verify"
                                :disabled="!breeze.registration"
                                no-margin
                                centered
                                label="Enable Email Verification" />
                  <pg-check-box v-model="breeze.resets"
                                no-margin
                                centered
                                label="Enable Password Resets" />
                </column>
                <column centered v-if="breeze.verify">
                  <separator />
                  <p class="text-info hint-text">
                    <i class="fa fa-exclamation-triangle"></i>
                    You will have to <strong class="text-primary">add the verified middleware yourself</strong>
                    to the routes that can only be accessed by verified users.
                  </p>
                </column>
              </row>
            </basic-content-section>

            <basic-content-section heading="Laravel Fortify" prepend-separator v-if="modules.fortify">
              <row>
                <column centered>
                  <pg-check-box v-model="fortify.disableViewRoutes" no-margin centered color-class="danger" label="Disable View Routes" />
                  <separator />
                </column>
                <column centered>
                  <pg-check-box v-model="fortify.registration" no-margin centered label="Enable Registration" />
                  <pg-check-box v-model="fortify.verify" no-margin centered label="Enable Email Verification" />
                  <pg-check-box v-model="fortify.resets" no-margin centered label="Enable Password Resets" />
                  <pg-check-box v-model="fortify.update" no-margin centered label="Enable Password Updates" />
                  <pg-check-box v-model="fortify.twoFactor" no-margin centered label="Enable Two-Factor Authentication" />
                </column>
                <column centered v-if="fortify.verify">
                  <separator />
                  <p class="text-info hint-text no-margin">
                    <i class="fa fa-exclamation-triangle"></i>
                    You will have to <strong class="text-primary">add the verified middleware yourself</strong>
                    to the routes that can only be accessed by verified users.
                  </p>
                </column>
              </row>
            </basic-content-section>
          </column>
        </row>
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
import BasicContentSection from '@/components/Content/BasicContentSection';
import AppName from '@/components/Typography/Decorated/AppName';
import ExternalLink from '@/components/Navigation/ExternalLink';

export default {
  name: 'AuthenticationManager',
  mixins: [asyncImports, mutations],
  components: {
    ExternalLink,
    AppName,
    BasicContentSection,
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

      authEnabled: false,

      modules: {
        ui: false,
        breeze: true,
        fortify: false,
      },

      ui: {
        registration: true,
        resets: true,
        verify: true,
        library: 'vue',
      },

      breeze: {
        registration: true,
        resets: true,
        verify: true,
      },

      fortify: {
        registration: true,
        resets: true,
        verify: true,
        update: true,
        twoFactor: false,
        disableViewRoutes: false,
      },
    };
  },
  computed: {
    persistableConfig() {
      return {
        enabled: this.authEnabled,
        modules: this.modules,
      };
    },
  },
  watch: {
    persistableConfig: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({ name: 'Auth Config', path: 'auth/config', value: v });
      },
      deep: true,
    },
    ui: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({ name: 'Laravel UI Config', path: 'auth/modules/ui', value: v });
      },
      deep: true,
    },
    fortify: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({ name: 'Laravel Fortify Config', path: 'auth/modules/fortify', value: v });
      },
      deep: true,
    },
    breeze: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({ name: 'Laravel Breeze Config', path: 'auth/modules/breeze', value: v });
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;
    await this.syncConfig();
    await this.syncUIModule();
    await this.syncBreezeModule();
    await this.syncFortifyModule();
    this.loading = false;

    this.$nextTick(() => {
      this.ready = true;
    });
  },
  methods: {
    async syncConfig() {
      const { data } = await this.mutation({ path: 'auth/config' });
      this.modules = data.value && data.value.modules ? data.value.modules : this.modules;
      this.authEnabled = data.value && data.value.enabled !== undefined ? data.value.enabled : this.authEnabled;
    },

    async syncUIModule() {
      const { data } = await this.mutation({ path: 'auth/modules/ui' });
      this.ui = data.value || this.ui;
    },

    async syncBreezeModule() {
      const { data } = await this.mutation({ path: 'auth/modules/breeze' });
      this.breeze = data.value || this.breeze;
    },

    async syncFortifyModule() {
      const { data } = await this.mutation({ path: 'auth/modules/fortify' });
      this.fortify = data.value || this.fortify;
    },

    onFortifyStateChanged(checked) {
      if (checked) {
        this.modules.breeze = false;
        this.modules.ui = false;
      }
    },

    onBreezeStateChanged(checked) {
      if (checked) {
        this.modules.fortify = false;
        this.modules.ui = false;
      }
    },

    onUIStateChanged(checked) {
      if (checked) {
        this.modules.fortify = false;
        this.modules.breeze = false;
      }
    },

    onBreezeRegistrationToggled(checked) {
      if (!checked) {
        this.breeze.verify = false;
      }
    },
  },
};
</script>

<style scoped>

</style>
