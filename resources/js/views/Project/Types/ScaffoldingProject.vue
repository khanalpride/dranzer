<template>
  <div>
    <fill-in-modal ref="generatingProjectModal">
      <row>
        <column>
          <p class="text-primary bold" v-html="buildStatus"></p>
          <indeterminate-progress-bar />
        </column>
      </row>
    </fill-in-modal>
    <fill-in-modal ref="cloneProgressModal">
      <row>
        <column>
          <p class="text-primary">Cloning...</p>
          <indeterminate-progress-bar />
        </column>
      </row>
    </fill-in-modal>
    <stick-up-modal ref="errorGeneratingProjectModal">
      <template slot="header">
        <row push10>
          <column centered>
            <p class="text-danger bold">
              <i class="fa fa-exclamation-triangle" />
              Error Generating Project
            </p>
            <separator />
          </column>
          <column>
            <p class="text-danger">
              <span class="bold">Your payment was successful</span>
              but an unexpected error occurred while trying to generate
              the project for you to download. Please try downloading again
              or contact support to resolve the issue.
            </p>
          </column>
        </row>
      </template>
    </stick-up-modal>
    <row>
      <column centered offset="2" size="8">
        <p v-if="readonly" class="text-primary">
          <i class="fa fa-lock" />
          <span
            v-tippy="{ placement: 'bottom', distance: 20 }"
            content="
            The project has already been downloaded and cannot be modified anymore (all changes will be discarded).
            It can, however, be downloaded again or cloned.
            "
          >
            Read-only
          </span>
        </p>

        <a
          href="#"
          class="text-green no-margin"
          @click.prevent="showProjectSummary"
          v-if="!readonly && pendingMutations === 0"
        >
          <i class="fa fa-check" /> No Pending Changes
        </a>

        <p v-if="pendingMutations > 0" :class="syncColor">
          <i class="fa fa-paper-plane" /> Syncing
          {{ getPendingMutationsAsString() }}
          <span v-if="pendingMutations > 1">
            +
            <span
              v-tippy="{ placement: 'right', distance: 20 }"
              :content="getCollapsedPendingMutationsAsString()"
            >
              {{ pendingMutations - 1 }} More
            </span>
          </span>
          <span>...</span>
        </p>
      </column>
    </row>

    <row push10>
      <column
        :offset="moduleOffset"
        :push10="!readonly && pendingMutations === 0"
        :size="moduleWidth"
      >
        <search-container
          ref="searchContainer"
          @cleared="hasResults = false"
          @closed="hasResults = false"
          @selected="activateModule"
          @search-complete="onModuleSearchComplete"
        />
      </column>

      <column v-if="restoring || loading" offset="5" size="2">
        <p class="text-center hint-text bold">Restoring module...</p>
        <indeterminate-progress-bar />
      </column>

      <column size="2" offset="5" v-if="loadingSummary">
        <indeterminate-progress-bar />
      </column>

      <column :offset="moduleOffset" :size="moduleWidth" v-if="filteredSummary">
        <content-card heading="Project Summary">
          <row>
            <column size="11" class="m-l-40">
              <pg-input v-model="summaryFilter" placeholder="Filter modules..." />
            </column>
            <column push15>
              <el-timeline>
                <el-timeline-item
                  v-for="(value, module) in filteredSummary"
                  v-if="value.mutations && value.mutations.length"
                  class="tailed"
                  :key="module.id"
                  hide-timestamp
                  type="danger"
                >
                  <row>
                    <column>
                      <p class="text-danger bold">{{ module }}</p>
                      <p class="text-info bold" v-if="value.description">
                        {{ value.description }}
                      </p>
                    </column>

                    <column push10>
                      <el-timeline>
                        <el-timeline-item type="success"
                                          :key="mutation.title"
                                          class="tailed"
                                          v-for="mutation in value.mutations">
                          <row>
                            <column>
                              <p class="text-green bold">
                                <span>{{ mutation.title }}</span>
                                <span class="text-info m-l-10" v-if="mutation.price && mutation.price > 0">
                                ${{ mutation.price }}
                            </span>
                              </p>
                            </column>
                            <column size="6" class="m-l-10">
                              <p class="text-info hint-text no-margin" v-if="mutation.desc">
                                <i class="fa fa-code"></i>
                                {{ mutation.desc }}
                              </p>
                            </column>
                          </row>
                        </el-timeline-item>
                      </el-timeline>
                    </column>
                  </row>

                </el-timeline-item>
              </el-timeline>
            </column>

            <column size="8" class="m-l-40 p-b-10" v-if="!Object.keys(filteredSummary).length">
              <text-block no-margin color="info" info hinted>No module matched your query.</text-block>
            </column>

            <column size="8" class="m-l-40" :push20="Object.keys(filteredSummary).length > 0">
                <button class="btn btn-green" @click="onDownloadProject">
                    <i class="fa fa-download"></i>
                    Download
                </button>
            </column>
          </row>
        </content-card>
      </column>

      <column
        v-if="module && !summary && !loadingSummary && !loading"
        :class="{ 'transparent-container': hasResults }"
        :offset="moduleOffset"
        :size="moduleWidth"
        :push10="hasResults"
      >
        <pending-import-progress
          :loading-text="`Initializing ${moduleTitle}...`"
          :module="module"
        />
        <row>
          <column>
            <component :is="module" :key="module" v-bind="moduleProps" />
          </column>
        </row>
      </column>
    </row>

    <row v-if="!loading && !restoring" :push10="(filteredSummary && Object.keys(filteredSummary).length > 0) || module !== null || hasResults">
      <column :offset="moduleOffset" size="3">
        <form-input-group>
          <button class="btn btn-info" style="width: 12%"
                  v-tooltip.left.15
                  content="Indicates that this is a cloned project. Click to open the source project."
                  @click="onGotoSourceProject"
                  v-if="project && project.cloned_from">
            C
          </button>
          <pg-input v-model="newProjectName"
                    validate
                    :focus-on-hover="false"
                    :validation-result="isNewProjectNameValid"
                    class="input-max-height"
                    style="width:45% !important;"
                    @input="debouncedProjectNameCB" />
          <pg-input class="input-max-height version-input text-danger text-center bold"
                    disabled
                    :value="`Laravel ${version}`" />
        </form-input-group>
      </column>

      <column :offset="moduleOffset" :size="3 + (moduleOffset === 1 ? 3 : 0)">
        <button
          v-tippy="{ placement: 'right' }"
          class="btn btn-danger pull-right"
          content="Delete Project"
          @click="$emit('delete')"
        >
          <i class="fa fa-close" />
        </button>

        <button
          v-tippy="{ placement: 'bottom' }"
          :disabled="mutations.length < 1"
          class="btn btn-primary pull-right m-r-5"
          content="Clone Project"
          @click="onCloneProject"
        >
          <i class="fa fa-clone" />
        </button>
        <button
          v-tippy="{ placement: 'bottom' }"
          :disabled="mutations.length < 1"
          class="btn btn-green pull-right m-r-5"
          content="Review and Download Project"
          @click="onReviewProject"
        >
          <i class="fa fa-download" />
        </button>
      </column>
    </row>
  </div>
</template>

<script>
import {
  mapActions, mapGetters, mapMutations, mapState,
} from 'vuex';

import debounce from 'lodash/debounce';

import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import laravelVersions from '@/data/core/laravel_versions';
import project from '@/mixins/project';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import SearchContainer from '@/components/Containers/SearchContainer';
import PendingImportProgress from '@/components/Progress/PendingImportProgress';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import FillInModal from '@/components/Modals/FillInModal';
import modules from '@/data/search/modules';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import StickUpModal from '@/components/Modals/StickUpModal';
import Separator from '@/components/Layout/Separator';
import ContentCard from '@/components/Cards/ContentCard';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import PgInput from '@/components/Forms/PgInput';
import ValidationHelpers from '@/helpers/validation_helpers';
import TextBlock from '@/components/Typography/Decorated/TextBlock';

const ControllersContainer = () => import(
  /* webpackChunkName: "chunks/s-c-cc" */ '@/components/Scaffolding/Controllers/ControllersContainer'
);
const DatabaseManager = () => import(
  /* webpackChunkName: "chunks/s-d-dm" */ '@/components/Scaffolding/Database/DatabaseManager'
);
const SchemaManager = () => import(
  /* webpackChunkName: "chunks/s-e-sc-sb" */ '@/components/Scaffolding/Database/Schema/SchemaManager'
);
const MiddlewareConfiguration = () => import(
  /* webpackChunkName: "chunks/s-m-mc" */ '@/components/Scaffolding/Middleware/MiddlewareConfiguration'
);
const ExceptionHandler = () => import(
  /* webpackChunkName: "chunks/s-e-eh" */ '@/components/Scaffolding/Exceptions/ExceptionHandler'
);
const ErrorPagesManager = () => import(
  /* webpackChunkName: "chunks/s-e-epm" */ '@/components/Scaffolding/Exceptions/ErrorPagesManager'
);
const TrustProxiesManager = () => import(
  /* webpackChunkName: "chunks/s-r-tpm" */ '@/components/Scaffolding/Request/TrustProxiesManager'
);
const TelescopeManager = () => import(
  /* webpackChunkName: "chunks/s-l-tm" */ '@/components/Scaffolding/Logging/Telescope/TelescopeManager'
);
const AuthenticationManager = () => import(
  /* webpackChunkName: "chunks/s-s-am" */ '@/components/Scaffolding/Security/AuthenticationManager'
);
const MailDriverContainer = () => import(
  /* webpackChunkName: "chunks/s-m-mdc" */ '@/components/Scaffolding/Mail/MailDriverContainer'
);
const RelationsManager = () => import(
  /* webpackChunkName: "chunks/s-e-r-rm" */ '@/components/Scaffolding/Eloquent/RelationsManager'
);
const UserInterfaceManager = () => import(
  /* webpackChunkName: "chunks/s-ui-uim" */ '@/components/Scaffolding/UserInterface/UserInterfaceManager'
);
const LoggingManager = () => import(
  /* webpackChunkName: "chunks/s-l-lm" */ '@/components/Scaffolding/Logging/LoggingManager'
);
const RolesManager = () => import(
  /* webpackChunkName: "chunks/s-a-rm" */ '@/components/Scaffolding/Authorization/RolesManager'
);
const ApiManager = () => import(
  /* webpackChunkName: "chunks/s-a-am" */ '@/components/Scaffolding/API/ApiManager'
);
const NginxConfigurationContainer = () => import(
  /* webpackChunkName: "chunks/s-d-ncc" */ '@/components/Scaffolding/Deployment/NginxConfigurationContainer'
);
const TaskSchedulerContainer = () => import(
  /* webpackChunkName: "chunks/s-s-tsc" */ '@/components/Scaffolding/Scheduling/TaskSchedulerContainer'
);
const JobsContainer = () => import(
  /* webpackChunkName: "chunks/s-q-jc" */ '@/components/Scaffolding/Queues/JobsContainer'
);
const HorizonManager = () => import(
  /* webpackChunkName: "chunks/s-q-hm" */ '@/components/Scaffolding/Queues/HorizonManager'
);
const SupervisorContainer = () => import(
  /* webpackChunkName: "chunks/s-q-sc" */ '@/components/Scaffolding/Queues/SupervisorContainer'
);
const BrandingManager = () => import(
  /* webpackChunkName: "chunks/s-a-bm" */ '@/components/Scaffolding/App/BrandingManager'
);
const LaravelDecomposerManager = () => import(
  /* webpackChunkName: "chunks/s-d-ldm" */ '@/components/Scaffolding/DevTools/LaravelDecomposerManager'
);
const LaravelDebugBarManager = () => import(
  /* webpackChunkName: "chunks/s-d-dbm" */ '@/components/Scaffolding/DevTools/LaravelDebugBarManager'
);
const LaravelIdeHelperManager = () => import(
  /* webpackChunkName: "chunks/s-d-ide" */ '@/components/Scaffolding/DevTools/LaravelIdeHelperManager'
);
const CookieConsentManager = () => import(
  /* webpackChunkName: "chunks/s-u-cc" */ '@/components/Scaffolding/Utilities/CookieConsentManager'
);
const FormRequestValidationContainer = () => import(
  /* webpackChunkName: "chunks/s-v-fv" */ '@/components/Scaffolding/Validation/FormRequestValidationContainer'
);
const NotificationsManager = () => import(
  /* webpackChunkName: "chunks/s-n-nm" */ '@/components/Scaffolding/Notifications/NotificationsManager'
);
const MailableManager = () => import(
  /* webpackChunkName: "chunks/s-m-mm" */ '@/components/Scaffolding/Mail/MailableManager'
);
const TailwindManager = () => import(
  /* webpackChunkName: "chunks/s-f-s-t-tm" */ '@/components/Scaffolding/Frontend/Styling/Tailwind/TailwindManager'
);
const CashierContainer = () => import(
  /* webpackChunkName: "chunks/s-p-p-cc" */ '@/components/Scaffolding/Payments/CashierContainer'
);
const ESLintManager = () => import(
  /* webpackChunkName: "chunks/s-l-elm" */ '@/components/Scaffolding/Linters/ESLintManager'
);
const LaravelMixContainer = () => import(
  /* webpackChunkName: "chunks/s-a-m/lmc" */ '@/components/Scaffolding/Assets/Mix/LaravelMixContainer'
);
const CustomBladePartialsManager = () => import(
  /* webpackChunkName: "chunks/s-u-c-b/cbm" */ '@/components/Scaffolding/UserInterface/Custom/Blade/CustomBladePartialsManager'
);
const VueManager = () => import(
  /* webpackChunkName: "chunks/s-f-v-vm" */ '@/components/Scaffolding/Frontend/Vue/VueManager'
);

const { axios } = window;

export default {
  name: 'ScaffoldingProject',
  components: {
    TextBlock,
    PgInput,
    FormInputGroup,
    ContentCard,
    Separator,
    StickUpModal,
    PgCheckBox,
    FillInModal,
    IndeterminateProgressBar,
    Row,
    Column,
    SearchContainer,
    PendingImportProgress,
    ControllersContainer,
    DatabaseManager,
    SchemaManager,
    MiddlewareConfiguration,
    ErrorPagesManager,
    ExceptionHandler,
    TrustProxiesManager,
    LoggingManager,
    TelescopeManager,
    AuthenticationManager,
    MailDriverContainer,
    RelationsManager,
    UserInterfaceManager,
    RolesManager,
    ApiManager,
    NginxConfigurationContainer,
    TaskSchedulerContainer,
    JobsContainer,
    HorizonManager,
    SupervisorContainer,
    BrandingManager,
    LaravelDecomposerManager,
    LaravelDebugBarManager,
    LaravelIdeHelperManager,
    CookieConsentManager,
    FormRequestValidationContainer,
    NotificationsManager,
    MailableManager,
    TailwindManager,
    CashierContainer,
    ESLintManager,
    LaravelMixContainer,
    CustomBladePartialsManager,
    VueManager,
  },
  mixins: [asyncImports, mutations, project],
  data() {
    return {
      loading: false,

      restoring: false,

      syncing: false,

      newProjectName: '',

      prolongedSyncing: false,

      paddleIFrameLoaded: false,

      syncColor: 'text-primary',

      prolongedSyncingColor: 'text-danger',

      version: null,

      versions: [],

      hasResults: false,

      module: null,

      moduleProps: {},

      moduleTitle: null,

      loadingSummary: false,

      summary: null,

      summaryFilter: '',

      projectBaseCost: process.env.MIX_BASE_PRICE,

      projectTotalCost: 15.00,

      projectDynamicCost: 0,

      buildStatus: null,

      updateProjectNameRequestCancelToken: null,

      debouncedProjectNameCB: null,
    };
  },
  computed: {
    ...mapState('project', ['mutations']),
    ...mapGetters('project', ['projectId']),

    moduleWidth() {
      if (!this.module) {
        return 8;
      }

      return this.isMaxWidthModule(this.module) ? 10 : 8;
    },

    moduleOffset() {
      if (!this.module) {
        return 2;
      }

      return this.isMaxWidthModule(this.module) ? 1 : 2;
    },

    readonly() {
      return !this.project ? true : this.project.downloaded;
    },

    isNewProjectNameValid() {
      return { tipPlacement: 'bottom', ...ValidationHelpers.isValidProjectName(this.newProjectName) };
    },

    filteredSummary() {
      const query = this.summaryFilter.toLowerCase().trim();

      const { summary } = this;

      if (!summary || Object.keys(summary).length === 0) {
        return null;
      }

      if (query === '') {
        return summary;
      }

      const filtered = {};

      // eslint-disable-next-line no-restricted-syntax
      for (const module in summary) {
        // eslint-disable-next-line no-prototype-builtins
        if (summary.hasOwnProperty(module)) {
          if (module.toLowerCase().indexOf(query) > -1) {
            filtered[module] = summary[module];
          }
        }
      }

      return filtered;
    },
  },
  async created() {
    this.debouncedProjectNameCB = debounce(async (updatedName) => this.onProjectNameChanged(updatedName), 500);

    this.SET_PROJECT_COMPONENT(this);

    this.loading = true;

    await this.syncCommonMutations();

    this.versions = laravelVersions;

    // eslint-disable-next-line prefer-destructuring
    this.version = laravelVersions[0];

    this.newProjectName = this.project.name;

    this.loading = false;

    window.addEventListener('beforeunload', (e) => {
      if (this.pendingMutations > 0) {
        e.preventDefault();
        e.returnValue = 'Syncing changes...Are you sure you want to leave?';
      }
    });
  },
  async mounted() {
    await this.syncModule();
  },
  methods: {
    ...mapActions('app', ['getProjects']),
    ...mapMutations('project', ['SET_PROJECT', 'SET_PROJECT_NAME', 'SET_PROJECT_COMPONENT']),

    async onProjectNameChanged() {
      const updatedName = this.newProjectName.trim();

      if (updatedName === '') {
        return;
      }

      if (this.updateProjectNameRequestCancelToken) {
        this.updateProjectNameRequestCancelToken('Aborting previous request...');
      }

      const response = await axios.post('/projects/rename', { name: updatedName, projectId: this.projectId }, {
        cancelToken: new axios.CancelToken((c) => {
          this.updateProjectNameRequestCancelToken = c;
        }),
      }).catch(() => {});

      if (response.status === 200) {
        this.SET_PROJECT_NAME(updatedName);
        document.querySelector('title').innerHTML = `${updatedName} - ${this.app.name}`;
      }
    },

    isMaxWidthModule(moduleKey) {
      const maxWidthModules = ['SchemaManager', 'UserInterfaceManager'];

      return maxWidthModules.includes(moduleKey);
    },

    async syncCommonMutations() {
      await this.mutation({ path: 'database/type' });
    },

    async syncModule() {
      let lastActiveModule = null;

      this.restoring = true;

      const { data } = await axios.post('/settings/project/get', {
        name: 'module',
        projectId: this.projectId,
      });

      lastActiveModule = data.value || null;

      this.$nextTick(() => {
        if (this.$refs.searchContainer && lastActiveModule) {
          this.$refs.searchContainer.setLastActivatedModuleKey(
            lastActiveModule.key,
          );
        }
      });

      this.restoring = false;

      this.$emit('restored');

      this.activateModule(lastActiveModule);
    },

    focusSearchInput() {
      this.$refs.searchContainer.focus();
    },

    activateModule(m) {
      if (!m) {
        return;
      }

      let module = m;

      if (!module.key) {
        module = modules.map((mod) => mod.children).flat().find((c) => c.routeKey === m);

        if (!module) {
          return;
        }
      }

      if (module.cache !== undefined && module.cache === false) {
        this.module = null;
      }

      this.loadingSummary = false;
      this.summary = null;

      const moduleKey = module.key;
      const moduleTitle = module.originalTitle || module.title;

      this.$nextTick(() => {
        this.addAsyncImport(moduleKey);

        this.module = moduleKey;
        this.moduleProps = module.props || {};

        this.moduleTitle = moduleTitle.replace('Configure', '').replace('Manage', '').trim();

        axios.post('/settings/project', {
          name: 'module',
          value: { key: moduleKey, title: moduleTitle },
          projectId: this.projectId,
        });
      });
    },

    async onDownloadProject() {
      this.buildStatus = 'Building project...';

      this.$refs.generatingProjectModal.show();

      await axios.post('/projects/build', { projectId: this.projectId });

      this.$refs.generatingProjectModal.hide();
    },

    async onReviewProject() {
      await this.onDownloadProject();
      return;

      if (this.summary) {
        await this.onDownloadProject();
        return;
      }

      await this.showProjectSummary();
    },

    async showProjectSummary() {
      if (this.summary) {
        return;
      }

      this.loadingSummary = true;
      const { data } = await axios.post('/summary', { projectId: this.projectId });
      this.loadingSummary = false;

      this.summary = data.summary;
      this.projectTotalCost = data.totalCost;
      this.projectDynamicCost = data.dynamicCost;
    },

    async onCloneProject() {
      this.$refs.cloneProgressModal.show();
      const { status, data } = await axios.post('/projects/clone', {
        projectId: this.projectId,
      });

      setTimeout(() => {
        this.$refs.cloneProgressModal.hide();

        setTimeout(() => {
          this.$nextTick(async () => {
            if (status === 200) {
              await this.SET_PROJECT(data.cloned);
              await this.$router.push(`/projects/${data.cloned.uuid}`);
            }
          });
        }, 500);
      }, 1000);
    },

    async onGotoSourceProject() {
      if (!this.project || !this.project.cloned_from) {
        return;
      }

      await this.$router.push(`/projects/${this.project.cloned_from}`);
    },

    onWhatsNewClick() {
      const v = this.version;

      setTimeout(() => {
        this.$nextTick(() => {
          this.version = v;
        });
      }, 500);
    },

    onVersionChange() {
      axios.post('/settings/project', {
        name: 'version',
        value: this.version,
        projectId: this.projectId,
      });
    },

    onModuleSearchComplete(matchedModulesCount) {
      this.hasResults = matchedModulesCount > 0;
    },
  },
};
</script>

<style>
.version-input {
    background-color: #ffffff !important;
    opacity: 0.6 !important;
}
</style>
