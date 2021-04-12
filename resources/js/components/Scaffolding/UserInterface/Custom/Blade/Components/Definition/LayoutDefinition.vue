<template>
    <scaffolding-component-container :heading="`${heading || 'Configure Layout Theme and Assets'}`" :loading="loading || fetchingMutations">
        <row>
            <column>
                <stick-up-modal ref="layoutProcessingModal">
                    <row>
                        <column>
                            <p class="text-info">Processing Base Layout...</p>
                        </column>
                        <column>
                            <indeterminate-progress-bar />
                        </column>
                    </row>
                </stick-up-modal>
            </column>
        </row>
        <row>
            <column>
                <usage-instructions modal-heading="Assets Definition">
                    <p>This section allows you to define the static assets for your template / base layout.</p>
                </usage-instructions>
            </column>
            <column>
                <row>
                    <column centered>
                        <basic-content-section heading="1. Layout Template / Theme (zipped)">
                            <template slot="help">
                              Use the uploader in this section to upload a template / theme for your layout.
                              This includes selecting a zipped theme (either drop it into the uploader or manually select it)
                              and finally choosing the base layout file
                              (usually index.html which will automatically be selected if present in your zipped theme).
                            </template>
                            <layout-theme-manager ref="themeManager"
                                                  :layout-id="layoutId"
                                                  :layout-name="layoutName"
                                                  @has-theme="hasThemeFile = true"
                                                  @processed="processTheme"
                                                  @removing="handleThemeFileRemoving"
                                                  @removed="handleThemeFileRemoved" />
                        </basic-content-section>
                    </column>

                    <column centered size="8" offset="2" v-if="themeHtmlFiles.length">
                        <row>
                            <column>
                                <separator />
                            </column>

                            <column>
                                <p class="text-primary">
                                    <span>
                                      Base Layout File
                                        <inline-help-link>
                                          This is the html file that contains the common blocks of code (partials) and
                                          is used (extended) by other views as the skeleton.
                                          Simply put, this file (usually index.html) has the functionality / design
                                          used by two or more pages on your website.
                                        </inline-help-link>
                                    </span>
                                </p>
                            </column>

                            <column>
                                <base-view-manager :theme-files="themeHtmlFiles"
                                                   :index-file="baseLayoutFile"
                                                   @file-changed="onBaseLayoutFileChanged($event, true)" />
                            </column>
                        </row>
                    </column>
                    <column v-if="hasThemeFile && baseLayoutFile">
                        <basic-content-section heading="2. Base Layout Assets"
                                               prepend-separator>
                            <template slot="help">
                              This section contains the stylesheets, scripts and images from the base layout <span
                              class="text-complete bold">{{ str.ellipse(fs.fn(baseLayoutFile.path)) }}</span>
                            </template>
                          <assets-manager ref="assetsManager"
                                          :asset-paths="assetPaths"
                                          :base-layout-contents="baseLayoutContents"
                                          :layout-id="layoutId"
                                          :persisted-assets="assets"
                                          :process-base-layout-contents="themeZipResponse !== null"
                                          @processed-assets="assets = $event" />
                        </basic-content-section>
                    </column>
                    <column size="8" offset="2" v-if="baseLayoutFile && baseLayoutContents">
                        <basic-content-section heading="3. Partials and Views" prepend-separator>
                            <template slot="help">
                              This section contains the partials and views for the layout. Use the selector list below
                              (generated from <span
                              class="text-complete bold">{{ str.ellipse(fs.fn(baseLayoutFile.path)) }}</span>) to create
                              new partials.<br/><br/><span class="text-danger bold">Selectors are extracted from these tags only: <span
                              class="text-complete">body, div, section, header, main, nav, ul, ol</span></span><br/><br/>Once
                              you've created the partials, head on to the views tab to create new views. Each view
                              automatically extends this layout.<br/><br/>
                              <span class="text-danger bold">
                                <i class="fa fa-warning"></i>
                                  It is not possible to create a view that extends a different layout.
                                  Each layout has views strictly extending itself.
                                  If you have two or more views that must extend a different layout, create a dedicated layout for them.
                              </span>
                            </template>
                            <row>
                                <column>
                                    <tabs-manager :tabs="baseLayoutTabs" :path="`ui/settings/custom/base/layout/tabs/active/${layoutId}`">
                                        <template slot="Partials">
                                          <partials-manager ref="partialsManager"
                                                            :base-layout-contents="baseLayoutContents"
                                                            :layout-id="layoutId"
                                                            :layout-name="layoutName"
                                                            :layout-path="layoutPath"
                                                            :persisted-partials="partials"
                                                            @updated="partials = $event"/>
                                        </template>
                                        <template slot="Views">
                                            <views-manager :layout="layout"
                                                           :theme-files="themeHtmlFiles"
                                                           :index-file="baseLayoutFile"
                                                           @view-count-changed="viewCount = $event" />
                                        </template>
                                    </tabs-manager>
                                </column>
                            </row>
                        </basic-content-section>
                    </column>

                  <column size="8" offset="2" v-if="baseLayoutFile && baseLayoutContents">
                    <basic-content-section heading="4. Styling" prepend-separator>
                      <row>
                        <column centered>
                          <pg-check-box v-model="styling.bootstrapPagination"
                                        centered
                                        no-margin
                                        label="Use Bootstrap Style Pagination" @change="persistStyling" />
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
import { mapGetters } from 'vuex';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import UsageInstructions from '@/components/Help/UsageInstructions';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import Separator from '@/components/Layout/Separator';
import BasicContentSection from '@/components/Content/BasicContentSection';
import TabsManager from '@/components/Tabs/TabsManager';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import StickUpModal from '@/components/Modals/StickUpModal';
import InlineHelpLink from '@/components/Help/InlineHelpLink';
import PartialsManager from '@/components/Scaffolding/UserInterface/Custom/Blade/Components/Definition/Views/PartialsManager';
import BaseViewManager from '@/components/Scaffolding/UserInterface/Custom/Blade/Components/Definition/Views/BaseViewManager';
import LayoutThemeManager from '@/components/Scaffolding/UserInterface/Custom/Blade/Components/Definition/Assets/LayoutThemeManager';
import AssetsManager from '@/components/Scaffolding/UserInterface/Custom/Blade/Components/Definition/Assets/AssetsManager';
import ViewsManager from '@/components/Scaffolding/UserInterface/Custom/Blade/Components/Definition/Views/ViewsManager';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';

const { axios } = window;

export default {
  name: 'LayoutDefinition',
  props: {
    heading: String,
    layout: {},
    layoutId: [String, Number],
    layoutName: [String],
    layoutPath: [String],
  },
  mixins: [asyncImports, mutations],
  components: {
    PgCheckBox,
    ViewsManager,
    InlineHelpLink,
    AssetsManager,
    LayoutThemeManager,
    BaseViewManager,
    PartialsManager,
    StickUpModal,
    IndeterminateProgressBar,
    TabsManager,
    BasicContentSection,
    Separator,
    UsageInstructions,
    Column,
    Row,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,
      loadingThemeFile: false,

      assets: {
        scripts: [],
        stylesheets: [],
        images: [],
        videos: [],
      },

      hasThemeFile: false,

      themeZipResponse: null,

      baseLayoutContents: null,

      baseLayoutFile: null,

      assetPaths: [],

      themeHtmlFiles: [],

      partials: [],

      views: [],

      viewCount: 0,

      styling: {
        bootstrapPagination: false,
      },
    };
  },
  computed: {
    ...mapGetters('project', ['projectId']),

    baseLayoutTabs() {
      return [
        {
          id: 'Partials',
          label: `Partials (${this.partials.length})`,
        },
        {
          id: 'Views',
          label: `Views (${this.viewCount})`,
        },
      ];
    },
  },
  async created() {
    this.loading = true;

    this.registerMutable('Scripts', `assets/template/scripts/${this.layoutId}`, {
      then: (value) => {
        this.assets.scripts = value || [];
      },
    });

    this.registerMutable('Stylesheets', `assets/template/stylesheets/${this.layoutId}`, {
      then: (value) => {
        this.assets.stylesheets = value || [];
      },
    });

    this.registerMutable('Images', `assets/template/images/${this.layoutId}`, {
      then: (value) => this.assets.images = value || [],
    });

    this.registerMutable('Videos', `assets/template/videos/${this.layoutId}`, {
      then: (value) => this.assets.videos = value || [],
    });

    this.registerMutable('Theme Html Files', `ui/settings/layouts/theme/main/files/html/${this.layoutId}`, {
      then: (value) => {
        this.themeHtmlFiles = value || [];
      },
    });

    this.registerMutable('Class List', `ui/layout/base/selectorList/${this.layoutId}`, {
      then: (value) => this.baseLayoutSelectorList = value || [],
    });

    this.registerMutable('Base Layout File', `ui/settings/layouts/theme/main/files/index/${this.layoutId}`, {
      then: async (value) => {
        this.baseLayoutFile = value || null;
        if (value) {
          await this.handleBaseLayoutChanged(value, false);
        }
      },
    });

    await this.syncPartials();
    await this.syncViews();
    await this.syncStyling();

    this.loading = false;
  },
  methods: {
    async syncPartials() {
      const { data } = await this.mutation({ path: `ui/partials/${this.layoutId}/`, like: true, refresh: true });
      this.partials = data.value ? data.value.map((v) => v.value) : [];
    },

    async syncViews() {
      const { data } = await this.mutation({ path: `ui/views/${this.layout.id}/`, like: true, refresh: true });
      this.views = data.value ? data.value.map((v) => v.value) : [];
      this.viewCount = this.views.length;
    },

    async syncStyling() {
      const { data } = await this.mutation({ path: `template/styling/${this.layoutId}` });
      this.styling = data.value || this.styling;
    },

    async processTheme(e) {
      this.themeHtmlFiles = [];
      this.baseLayoutFile = null;

      const {
        files, baseLayoutFile, response, assetPaths,
      } = e;

      this.assetPaths = assetPaths;

      if (response) {
        this.hasThemeFile = true;
      }

      this.themeZipResponse = response;

      this.themeHtmlFiles = files || [];

      this.persistHtmlFiles();

      await this.onBaseLayoutFileChanged(baseLayoutFile);
    },

    async onBaseLayoutFileChanged(baseLayoutFile, processBaseLayoutContents = false) {
      this.baseLayoutFile = baseLayoutFile;

      this.processFonts();

      if (baseLayoutFile) {
        this.$refs.layoutProcessingModal.show();
        await this.handleBaseLayoutChanged(baseLayoutFile);

        if (processBaseLayoutContents && this.$refs.assetsManager) {
          await this.resetLayout(true);
        }

        await this.promises.sleep(1000);

        this.$refs.layoutProcessingModal.hide();
      }
    },

    async resetLayout(processBaseLayoutContents) {
      if (processBaseLayoutContents) {
        await this.$refs.assetsManager.onProcessBaseLayoutContents();
      }

      const deletionPaths = [
        `assets/mix/config/pp/groups/scripts/${this.layoutId}`,
        `assets/mix/config/pp/groups/stylesheets/${this.layoutId}`,
        `ui/views/${this.layoutId}/*`,
        `ui/partials/${this.layoutId}/*`,
      ];

      await this.bulkDeleteMutations(deletionPaths, {
        then: () => {
          this.views = [];
          this.partials = [];
        },
        bulk: true,
      });
    },

    async clearLayout() {
      await this.resetLayout();

      this.persistFonts([]);

      await this.deleteMutation(`ui/custom/content-wrapper/${this.layoutId}`, {
        then: () => {
          this.baseLayoutFile = null;
        },
      });

      await this.deleteMutation(`ui/settings/layouts/theme/main/files/index/${this.layoutId}`, {
        then: () => {
          this.baseLayoutFile = null;
        },
      });

      await this.deleteMutation(`ui/settings/layouts/theme/main/files/html/${this.layoutId}`, {
        then: () => {
          this.baseLayoutFile = null;
        },
      });
    },

    processFonts() {
      const fontExtensions = [
        '.woff',
        '.woff2',
        '.ttf',
        '.otf',
        '.eot',
      ];

      const fonts = this.assetPaths.filter((a) => fontExtensions.includes(this.fs.ext(a)))
        .map((f) => {
          let path = f.split(this.fs.sep());
          path = path.length > 1 ? path.slice(1) : path[0];
          return {
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            asset: path.join(this.fs.sep()),
            enabled: true,
          };
        });

      this.persistFonts(fonts);
    },

    persistFonts(fonts) {
      const payload = {
        name: 'Theme Fonts',
        path: `assets/template/fonts/${this.layoutId}`,
        value: fonts,
      };

      this.mutate(payload);
    },

    persistStyling() {
      const payload = {
        name: 'Layout Style',
        path: `template/styling/${this.layoutId}`,
        value: this.styling,
      };

      this.mutate(payload);
    },

    async clearAssetsManager() {
      if (this.$refs.assetsManager) {
        await this.$refs.assetsManager.clear();
      }
    },

    handleThemeFileRemoving() {
      this.clearLayout();

      let payload = {
        name: 'Theme Fonts',
        path: `assets/template/fonts/${this.layoutId}`,
        value: [],
      };

      this.mutate(payload);

      payload = {
        name: 'Assets',
        path: `assets/mix/copy/${this.layoutId}`,
        value: [],
      };

      this.mutate(payload);
    },

    handleThemeFileRemoved() {
      this.themeHtmlFiles = [];
      this.baseLayoutFile = null;
      this.hasThemeFile = false;
    },

    async handleBaseLayoutChanged(baseLayoutFile, persist = true) {
      if (!baseLayoutFile || !baseLayoutFile.path) {
        return;
      }

      if (persist) {
        this.persistBaseLayoutFile();
      }

      await this.setBaseLayoutContents(baseLayoutFile);
    },

    async setBaseLayoutContents(baseLayoutFile) {
      this.baseLayoutContents = await this.getBaseLayoutContents(baseLayoutFile);
    },

    async getBaseLayoutContents(baseLayoutFile) {
      return this.themeZipResponse
        ? this.themeZipResponse.file(baseLayoutFile.path).async('string')
        : this.getPersistedBaseLayoutContents(baseLayoutFile);
    },

    async getPersistedBaseLayoutContents(baseLayoutFile) {
      const { data } = await axios.post('/assets/contents/zip/single', {
        key: this.layoutId,
        module: 'layout',
        projectId: this.projectId,
        filePath: baseLayoutFile.path,
      });

      return data && data.contents ? data.contents : null;
    },

    persistBaseLayoutFile() {
      if (!this.baseLayoutFile) {
        return;
      }

      this.$nextTick(() => {
        const payload = {
          name: 'Base Layout File',
          path: `ui/settings/layouts/theme/main/files/index/${this.layoutId}`,
          value: this.baseLayoutFile,
        };

        this.mutate(payload);
      });
    },

    persistHtmlFiles() {
      const payload = {
        name: 'Theme Html Files',
        path: `ui/settings/layouts/theme/main/files/html/${this.layoutId}`,
        value: this.themeHtmlFiles,
      };

      this.mutate(payload);
    },

    showUsageModal() {
      this.$refs.usageModal.show();
    },
  },
};
</script>

<style scoped>

</style>
