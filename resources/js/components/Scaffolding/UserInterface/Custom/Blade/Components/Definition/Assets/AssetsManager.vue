<template>
    <div>
        <row v-if="loading">
            <column centered size="4" offset="4">
                <indeterminate-progress-bar />
            </column>
        </row>
        <row v-else>
            <column size="8" offset="2">
                <tabs-manager :tabs="assetTabs" :path="`ui/custom/assets/definition/tabs/active/${layoutId}`">
                    <template :slot="category.id" v-for="category in assetTabs">
                        <asset-category :ref="`assetCat${category.id}`"
                                        :key="category.id"
                                        :filter-category="category.title"
                                        :filters="category.assets"
                                        :link-extraction-tag="category.extractionTag"
                                        :link-extraction-attribute="category.extractionAttr"
                                        :match-allowed-callback="category.matchAllowedCallback"
                                        @delete="handleAssetDeleted(category.name, $event)"
                                        @delete-all="handleDeleteAllAssets(category.name)"
                                        @state-changed="handleAssetStateChanged(category.name, $event)"
                                        @check-all-toggled="handleCheckAllToggled(category.name, $event)" />
                    </template>
                </tabs-manager>
            </column>
        </row>
    </div>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import TabsManager from '@/components/Tabs/TabsManager';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import AssetCategory from '@/components/Scaffolding/Assets/Template/AssetCategory';

export default {
  name: 'AssetsManager',
  props: {
    layoutId: [String, Number],
    processBaseLayoutContents: Boolean,
    baseLayoutContents: String,
    assetPaths: Array,
    persistedAssets: {},
  },
  mixins: [mutations],
  components: {
    IndeterminateProgressBar, AssetCategory, TabsManager, Column, Row,
  },
  data() {
    return {
      loading: false,

      jsModules: [],

      assets: this.persistedAssets || {
        scripts: [],
        stylesheets: [],
        images: [],
        videos: [],
      },

      scriptGroups: [],
      stylesheetGroups: [],
    };
  },
  watch: {
    baseLayoutContents: {
      async handler(v) {
        if (v && this.assetTabs && this.processBaseLayoutContents) {
          await this.onProcessBaseLayoutContents();
        }
      },
      immediate: true,
    },
  },
  computed: {
    assetTabs() {
      return [
        {
          id: 'SS',
          label: `Stylesheets (${this.assets.stylesheets.length})`,
          title: 'Stylesheet',
          name: 'stylesheets',
          assets: this.assets.stylesheets,
          extractionAttr: 'href',
          extractionTag: 'link',
          matchAllowedCallback: this.isValidStylesheet,
        },
        {
          id: 'SC',
          label: `Scripts (${this.assets.scripts.length})`,
          title: 'Script',
          name: 'scripts',
          assets: this.assets.scripts,
          extractionTag: 'script',
          extractionAttr: 'src',
          matchAllowedCallback: this.isValidScript,
        },
        {
          id: 'IM',
          label: `Images (${this.assets.images.length})`,
          title: 'Image',
          name: 'images',
          assets: this.assets.images,
          extractionAttr: 'src',
          matchAllowedCallback: this.isValidImageAsset,
        },
        {
          id: 'VI',
          label: `Videos (${this.assets.videos.length})`,
          title: 'Video',
          name: 'videos',
          assets: this.assets.videos,
          extractionAttr: 'src',
          matchAllowedCallback: this.isValidVideoAsset,
        },
      ];
    },

    isEmptyDefinition() {
      return !this.assets.scripts.length && !this.assets.stylesheets.length && !this.assets.images.length && !this.assets.videos.length;
    },
  },
  async created() {
    await this.syncGroups();
  },
  methods: {
    async syncGroups() {
      const { data } = await this.mutation({ path: `assets/mix/config/pp/groups/scripts/${this.layoutId}` });
      this.scriptGroups = data.value || [];

      {
        // eslint-disable-next-line no-shadow
        const { data } = await this.mutation({ path: `assets/mix/config/pp/groups/stylesheets/${this.layoutId}` });
        this.stylesheetGroups = data.value || [];
      }
    },

    async syncJsModules() {
      const { data } = await this.mutation({ path: `assets/mix/jsModules/modules/${this.layoutId}` });
      this.jsModules = data.value || [];
    },

    persistJsModules(modules) {
      const payload = {
        name: 'JS Modules',
        path: `assets/mix/jsModules/modules/${this.layoutId}`,
        value: modules,
      };

      this.mutate(payload);
    },

    async clear() {
      this.loading = true;

      this.assets = {
        scripts: [],
        stylesheets: [],
        images: [],
        videos: [],
      };

      await this.persistStylesheets();
      await this.persistScripts();
      await this.persistImages();
      await this.persistVideos();

      this.loading = false;
    },

    hasAsset(type, asset) {
      return this.assets[type].find((a) => a.asset === asset) !== undefined;
    },

    getAsset(type, asset) {
      return this.assets[type].find((a) => a.asset === asset);
    },

    addAsset(type, asset = '') {
      const actualPath = (this.assetPaths || []).find((path) => path.indexOf(asset) > -1) || null;

      this.assets[type].push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        enabled: true,
        asset,
        actualPath,
      });
    },

    parseLinks(prefix, text, linkExtractionAttribute, linkExtractionTag, matchAllowedCallback) {
      let m = null;

      const links = [];

      if (linkExtractionTag) {
        let block = '';

        const matches = this.rgx.getMatches(`<${linkExtractionTag}.*?>`, text);

        // eslint-disable-next-line no-shadow
        matches.forEach((m) => block += m);

        if (block !== '') {
          text = block;
        }
      }

      const pattern = linkExtractionAttribute ? `(?<=${linkExtractionAttribute}=["']).*?(?=["'])` : '.*';

      const regex = new RegExp(pattern, 'ig');

      // eslint-disable-next-line no-cond-assign
      while ((m = regex.exec(text)) !== null) {
        // This is necessary to avoid infinite loops with zero-width matches
        if (m.index === regex.lastIndex) {
          regex.lastIndex += 1;
        }

        m.forEach((match) => {
          const matchAllowed = matchAllowedCallback ? matchAllowedCallback(match) : true;

          if (!matchAllowed) {
            return false;
          }

          links.push(match);

          return true;
        });
      }

      return links;
    },

    isValidImageAsset(match) {
      if (!match) {
        return false;
      }

      if (match.indexOf('format=jpg') > -1) {
        return true;
      }

      // noinspection SpellCheckingInspection
      const validExtensions = [
        'svg', 'png', 'jpg', 'jpeg', 'jpe', 'bmp', 'gif', 'jif', 'jfif', 'jfi', 'webp',
        'tiff', 'tif', 'raw', 'arw', 'cr2', 'nrw', 'k25', 'dib', 'heif', 'heic', 'ind',
        'indd', 'indt', 'jp2', 'j2k', 'jpf', 'jpx', 'jpm', 'mj2', 'svg', 'svgz',
      ];

      // eslint-disable-next-line no-restricted-syntax
      for (const e of validExtensions) {
        if (match.toLowerCase().indexOf(`.${e}`) > -1) {
          return true;
        }
      }

      return false;
    },

    isValidVideoAsset(match) {
      if (!match) {
        return false;
      }

      // noinspection SpellCheckingInspection
      const validExtensions = [
        'mp4', 'flv', 'avi', 'mov', 'webm', 'mkv', 'mpeg', 'avchd', 'wmv', 'ts',
      ];

      // eslint-disable-next-line no-restricted-syntax
      for (const e of validExtensions) {
        const ext = this.fs.ext(match.toLowerCase());

        if (ext === `.${e.toLowerCase()}`) {
          return true;
        }
      }

      return false;
    },

    isValidStylesheet(match) {
      if (!match) {
        return false;
      }

      const ext = this.fs.ext(match.toLowerCase());

      return !ext.length < 4 && ext.substr(0, 4) === '.css';
    },

    isValidScript(match) {
      if (!match) {
        return false;
      }

      const ext = this.fs.ext(match.toLowerCase());

      return !ext.length < 3 && ext.substr(0, 3) === '.js';
    },

    persistScripts() {
      this.$nextTick(() => {
        const name = 'Template Scripts';
        const path = `assets/template/scripts/${this.layoutId}`;
        const value = this.assets.scripts.filter((s) => s.asset.trim() !== '');

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    persistStylesheets() {
      this.$nextTick(() => {
        const name = 'Template Stylesheets';
        const path = `assets/template/stylesheets/${this.layoutId}`;
        const value = this.assets.stylesheets.filter((s) => s.asset.trim() !== '');

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    persistImages() {
      this.$nextTick(() => {
        const name = 'Template Images';
        const path = `assets/template/images/${this.layoutId}`;
        const value = this.assets.images.filter((s) => s.asset.trim() !== '');

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    persistVideos() {
      this.$nextTick(() => {
        const name = 'Template Videos';
        const path = `assets/template/videos/${this.layoutId}`;
        const value = this.assets.videos.filter((s) => s.asset.trim() !== '');

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      });
    },

    handleAssetDeleted(type, asset) {
      const assetIndex = this.assets[type].findIndex((a) => a.id === asset.id);
      if (assetIndex > -1) {
        this.assets[type].splice(assetIndex, 1);
        this.handleAssetStateChanged(type);
      }
    },

    handleDeleteAllAssets(type) {
      this.assets[type] = [];
      this.handleAssetStateChanged(type);
    },

    handleAssetStateChanged(type, payload) {
      switch (type) {
        case 'stylesheets':
          this.persistStylesheets();
          break;
        case 'scripts':
          // eslint-disable-next-line no-case-declarations
          const { scriptGroups } = this;
          // eslint-disable-next-line no-case-declarations
          const path = payload && payload.asset ? payload.asset.asset : null;
          // eslint-disable-next-line no-case-declarations
          const enabled = payload && payload.checked;

          // eslint-disable-next-line no-case-declarations
          let updateGroup = false;

          if (path && !enabled) {
            scriptGroups.forEach((g) => {
              const script = g.assets.find((a) => a.path === path);
              if (script && !enabled) {
                g.assets = g.assets.filter((a) => a.path !== path);
                updateGroup = true;
              }
            });
          }

          if (updateGroup) {
            // eslint-disable-next-line no-case-declarations,no-shadow
            const payload = {
              name: 'Post Processing Asset Group',
              path: `assets/mix/config/pp/groups/scripts/${this.layoutId}`,
              value: scriptGroups,
            };

            this.mutate(payload);
          }

          this.persistScripts();

          // eslint-disable-next-line no-case-declarations
          const { scripts } = this.assets;
          // eslint-disable-next-line no-case-declarations
          const jsModules = scripts.map((script) => ({
            id: script.id,
            path: script.asset,
            filename: '',
          }));
          this.persistJsModules(jsModules);
          break;
        case 'images':
          this.persistImages();
          break;
        case 'videos':
          this.persistVideos();
          break;
        default:
          break;
      }
    },

    handleParsedLinks(type, parsed) {
      parsed.forEach((a) => {
        const asset = this.getAsset(type, a);
        if (!asset) {
          this.addAsset(type, a);
          this.handleAssetStateChanged(type);
        } else if (!asset.enabled) {
          this.handleAssetStateChanged(type);
        }
      });

      this.$emit('links-parsed', this.assets);
    },

    handleCheckAllToggled(type, checked) {
      this.assets[type].forEach((asset) => asset.enabled = checked);
      this.handleAssetStateChanged(type);
    },

    async onProcessBaseLayoutContents() {
      await this.clear();

      this.assetTabs.forEach((cat) => {
        const links = this.parseLinks('', this.baseLayoutContents, cat.extractionAttr, cat.extractionTag, cat.matchAllowedCallback);
        this.handleParsedLinks(cat.name, links);
      });

      this.$emit('processed-assets', this.assets);
    },
  },
};
</script>

<style scoped>

</style>
