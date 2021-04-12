<template>
  <scaffolding-component-container
    heading="Configure Exception Handler"
    :loading="loading || fetchingMutations"
  >
    <row>
      <column centered>
        <pg-check-box
          no-margin
          v-model="useSentry"
          centered
          label="Log Exceptions Using Sentry"
          @input="persistSentryOptions"
        />
        <separator />
      </column>

      <column v-if="useSentry" size="8" offset="2">
        <basic-content-section heading="DSN" :append-separator="false">
          <row push15>
            <column>
              <pg-labeled-input
                v-model="sentryDSN"
                label="Sentry DSN"
                @input="persistSentryOptions"
              />
            </column>
          </row>
        </basic-content-section>

        <basic-content-section class="m-t-5" heading="Sentry Exception Context">
          <row>
            <column centered>
              <pg-check-box
                v-model="attachUserId"
                no-margin
                centered
                label="Attach User Id"
                @change="persistSentryOptions"
              />
              <pg-check-box
                v-model="attachUserEmail"
                no-margin
                centered
                label="Attach User Email Address"
                @change="persistSentryOptions"
              />
            </column>
          </row>
        </basic-content-section>
      </column>

      <column size="8" offset="2">
        <basic-content-section heading="Do Not Report (Global)" :prepend-separator="useSentry">
          <row>
            <column size="10" offset="1">
              <pg-check-box
                v-model="doNotReportAuthenticationException"
                block
                color-class="danger"
                @change="persistDoNotReportOptions"
              >
                <template slot="label">
                  <tippy placement="right">
                    <template slot="trigger">
                      AuthenticationException
                    </template>

                    <span>\Illuminate\Auth\AuthenticationException</span>
                  </tippy>
                </template>
              </pg-check-box>

              <pg-check-box
                v-model="doNotReportAuthorizationException"
                block
                color-class="danger"
                @change="persistDoNotReportOptions"
              >
                <template slot="label">
                  <tippy placement="right">
                    <template slot="trigger">
                      AuthorizationException
                    </template>

                    <span
                    >\Illuminate\Auth\Access\AuthorizationException</span
                    >
                  </tippy>
                </template>
              </pg-check-box>

              <pg-check-box
                v-model="doNotReportHttpException"
                block
                color-class="danger"
                @change="persistDoNotReportOptions"
              >
                <template slot="label">
                  <tippy placement="right">
                    <template slot="trigger"> HttpException </template>

                    <span
                    >\Symfony\Component\HttpKernel⮒
                        \Exception\HttpException</span
                    >
                  </tippy>
                </template>
              </pg-check-box>

              <pg-check-box
                v-model="doNotReportModelNotFoundException"
                block
                color-class="danger"
                @change="persistDoNotReportOptions"
              >
                <template slot="label">
                  <tippy placement="right">
                    <template slot="trigger">
                      ModelNotFoundException
                    </template>

                    <span
                    >\Illuminate\Database\Eloquent⮒
                        \ModelNotFoundException</span
                    >
                  </tippy>
                </template>
              </pg-check-box>

              <pg-check-box
                v-model="doNotReportValidationException"
                block
                color-class="danger"
                @change="persistDoNotReportOptions"
              >
                <template slot="label">
                  <tippy placement="right">
                    <template slot="trigger"> ValidationException </template>

                    <span>\Illuminate\Validation\ValidationException</span>
                  </tippy>
                </template>
              </pg-check-box>
            </column>
          </row>
        </basic-content-section>
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
import BasicContentSection from '@/components/Content/BasicContentSection';

export default {
  name: 'ExceptionHandler',
  components: {
    BasicContentSection,
    ScaffoldingComponentContainer,
    Separator,
    PgLabeledInput,
    PgCheckBox,
    Column,
    Row,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,

      useSentry: false,

      sentryDSN: '',

      captureLogs: true,
      captureQueueJobs: true,
      captureQueries: true,
      captureBindings: false,

      attachUserId: true,
      attachUserEmail: true,

      doNotReportAuthenticationException: false,
      doNotReportAuthorizationException: false,
      doNotReportHttpException: false,
      doNotReportModelNotFoundException: false,
      doNotReportValidationException: false,
    };
  },
  async created() {
    this.loading = true;

    await this.syncSentryOptions();
    await this.syncDoNotReportOptions();

    this.loading = false;
  },
  mounted() {
    // Manually load remaining mutations
  },
  methods: {
    async syncSentryOptions() {
      const { data } = await this.mutation({
        path: 'exceptions/options/sentry',
      });

      if (!data.value) {
        return;
      }

      this.useSentry = data.value.enabled !== undefined ? data.value.enabled : this.useSentry;

      this.sentryDSN = data.value.dsn !== undefined ? data.value.dsn : this.sentryDSN;

      this.attachUserId = data.value.attachUserId !== undefined ? data.value.attachUserId : false;
      this.attachUserEmail = data.value.attachUserEmail !== undefined
        ? data.value.attachUserEmail
        : false;
    },

    async syncDoNotReportOptions() {
      const { data } = await this.mutation({
        path: 'exceptions/options/do-not-report',
      });

      if (!data.value) {
        return;
      }

      this.doNotReportAuthenticationException = data.value.authenticationException !== undefined
        ? data.value.authenticationException
        : false;
      this.doNotReportAuthorizationException = data.value.authorizationException !== undefined
        ? data.value.authorizationException
        : false;
      this.doNotReportHttpException = data.value.httpException !== undefined
        ? data.value.httpException
        : false;
      this.doNotReportModelNotFoundException = data.value.modelNotFoundException !== undefined
        ? data.value.modelNotFoundException
        : false;
      this.doNotReportValidationException = data.value.validationException !== undefined
        ? data.value.validationException
        : false;
    },

    persistSentryOptions() {
      this.$nextTick(() => {
        const name = 'Exception Handler Options';
        const path = 'exceptions/options/sentry';

        const value = {
          enabled: this.useSentry,
          dsn: this.sentryDSN,
          attachUserId: this.attachUserId,
          attachUserEmail: this.attachUserEmail,
        };

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    persistDoNotReportOptions() {
      this.$nextTick(() => {
        const name = 'Exception Handler Do Not Report Options';
        const path = 'exceptions/options/do-not-report';

        const value = {
          authenticationException: this.doNotReportAuthenticationException,
          authorizationException: this.doNotReportAuthorizationException,
          httpException: this.doNotReportHttpException,
          modelNotFoundException: this.doNotReportModelNotFoundException,
          validationException: this.doNotReportValidationException,
        };

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },
  },
};
</script>

<style scoped></style>
