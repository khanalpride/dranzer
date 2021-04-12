<template>
    <content-card :heading="heading">
        <div v-if="fetchingMutations || loading">
            <row>
                <column size="4" offset="4">
                    <p class="text-center">Restoring Configuration...</p>
                    <indeterminate-progress-bar />
                </column>
            </row>
        </div>

        <div v-else>
          <tabs-manager :tabs="tabs" :path="`config/assets/mix/tab/active/${layoutId}`">
            <template :slot="category.id" v-for="category in filteredTabs">
              <row :key="category.id" v-if="category.assets.length">
                <column v-if="category.assets.length > 1">
                  <pg-check-box :value="enabledCategoryAssets(category).length === categoryAssets(category).length"
                                @change="toggleEnableAllAssets($event, category)" />
                </column>
                <column>
                  <draggable v-model="assets">
                    <div :key="asset.sourcePath" v-for="asset in category.assets">
                      <pg-check-box v-model="asset.enabled"
                                    v-tippy="{onShow: () => asset.sourcePath.length > 30, placement: 'right'}"
                                    :label="getAssetLabel(asset)"
                                    :content="asset.sourcePath" />
                    </div>
                  </draggable>
                </column>
              </row>
              <row :key="category.id" v-else>
                <column push20 centered>
                  <p class="text-primary">No assets found.</p>
                </column>
              </row>
            </template>

            <template slot="custom">
              <row>
                <column v-if="customAssets.length">
                  <row>
                    <column class="p-b-10">
                      <form-input-title title="Source:Target Mapping" />
                    </column>
                    <column :key="asset.id" v-for="asset in customAssets">
                      <form-input-group compact :key="asset.id">
                        <pg-input v-model="asset.sourcePath" placeholder="Source Path" @input="sourcePathChanged($event, asset)" />
                        <pg-input v-model="asset.targetPath" placeholder="Target Path" />
                        <button class="btn btn-danger" @click="deleteAsset(asset)">
                          <i class="fa fa-close"></i>
                        </button>
                      </form-input-group>
                    </column>
                  </row>
                </column>
                <column>
                  <button class="btn btn-primary" @click="addAsset('', 'custom')">
                    <i class="fa fa-plus"></i>
                  </button>
                </column>
              </row>
            </template>
          </tabs-manager>
        </div>
    </content-card>
</template>

<script>
import Draggable from 'vuedraggable';
import Row from '@/components/Layout/Grid/Row';
import ContentCard from '@/components/Cards/ContentCard';
import Column from '@/components/Layout/Grid/Column';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import TabsManager from '@/components/Tabs/TabsManager';
import PgInput from '@/components/Forms/PgInput';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import FormInputTitle from '@/components/Typography/FormInputTitle';

export default {
  name: 'CopyAssets',
  props: {
    layoutId: [String, Number],
  },
  mixins: [asyncImports, mutations],
  components: {
    FormInputTitle,
    FormInputGroup,
    PgInput,
    TabsManager,
    PgCheckBox,
    Draggable,
    IndeterminateProgressBar,
    Column,
    ContentCard,
    Row,
  },
  data() {
    return {
      loading: false,

      assets: [],

      images: [],

      videos: [],

      fonts: [],

      custom: [],
    };
  },
  computed: {
    heading() {
      return `Copy Assets ${this.assets.length ? `(${this.enabledAssets.length} / ${this.assets.length})` : ''}`;
    },
    templateAssets() {
      return this.images
        .filter((i) => !this.assets.find((a) => a.sourcePath === i.name))
        .concat(this.fonts.filter((f) => !this.assets.find((a) => a.sourcePath === f.name)));
    },

    enabledAssets() {
      return this.assets.filter((a) => a.enabled);
    },

    tabs() {
      return ['image', 'video', 'font', 'custom'].map((t) => ({
        id: t,
        label: `${this.str.humanize(t === 'custom' ? t : this.str.pluralize(t))} (${this.assets.filter((a) => a.type === t).length})`,
        assets: this.assets.filter((a) => a.type === t),
      }));
    },

    filteredTabs() {
      return ['image', 'video', 'font'].map((t) => ({
        id: t,
        label: `${this.str.humanize(this.str.pluralize(t))} (${this.assets.filter((a) => a.type === t).length})`,
        assets: this.assets.filter((a) => a.type === t),
      }));
    },

    customAssets() {
      return this.assets.filter((a) => a.type === 'custom');
    },
  },
  watch: {
    assets: {
      handler(v) {
        const name = 'Assets';
        const path = `assets/mix/copy/${this.layoutId}`;
        const value = v;

        const payload = {
          name,
          path,
          value,
        };

        this.mutate(payload);
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;
    await this.syncAssets();

    await this.syncImages();
    await this.syncVideos();
    await this.syncFonts();
    await this.syncCustomAssets();

    if (!this.assets.length) {
      this.images.filter((i) => i.enabled).forEach((image) => this.addAsset(image.asset, 'image'));
      this.videos.filter((v) => v.enabled).forEach((video) => this.addAsset(video.asset, 'video'));
      this.fonts.filter((f) => f.enabled).forEach((font) => this.addAsset(font.asset, 'font'));
    } else {
      this.images.filter((i) => i.enabled).forEach((image) => {
        if (!this.assets.find((i) => i.sourcePath === image.asset)) {
          this.addAsset(image.asset, 'image');
        }
      });

      this.videos.filter((v) => v.enabled).forEach((video) => {
        if (!this.assets.find((i) => i.sourcePath === video.asset)) {
          this.addAsset(video.asset, 'video');
        }
      });

      this.fonts.filter((f) => f.enabled).forEach((font) => {
        if (!this.assets.find((i) => i.sourcePath === font.asset)) {
          this.addAsset(font.asset, 'font');
        }
      });

      this.assets = this.assets.filter((a) => {
        if (a.type === 'image') {
          return this.images.find((i) => i.enabled && a.sourcePath === i.asset) !== undefined;
        }
        if (a.type === 'video') {
          return this.videos.find((v) => v.enabled && a.sourcePath === v.asset) !== undefined;
        }
        if (a.type === 'font') {
          return this.fonts.find((f) => f.enabled && a.sourcePath === f.asset) !== undefined;
        }
        return true;
      });
    }

    this.loading = false;
  },
  methods: {
    async syncImages() {
      const { data } = await this.mutation({ path: `assets/template/images/${this.layoutId}` });
      this.images = data.value || [];
    },

    async syncVideos() {
      const { data } = await this.mutation({ path: `assets/template/videos/${this.layoutId}` });
      this.videos = data.value || [];
    },

    async syncFonts() {
      const { data } = await this.mutation({ path: `assets/template/fonts/${this.layoutId}` });
      this.fonts = data.value || [];
    },

    async syncCustomAssets() {
      const { data } = await this.mutation({ path: `assets/template/custom/${this.layoutId}` });
      this.custom = data.value || [];
    },

    async syncAssets() {
      const { data } = await this.mutation({ path: `assets/mix/copy/${this.layoutId}` });
      this.assets = data.value || [];
    },

    enabledCategoryAssets(category) {
      return this.assets.filter((a) => a.type === category.id && a.enabled);
    },

    categoryAssets(category) {
      return this.assets.filter((a) => a.type === category.id);
    },

    addAsset(sourcePath, type) {
      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);
      this.assets.push({
        id,
        sourcePath,
        type,
        targetPath: '',
        enabled: true,
      });

      this.$nextTick(() => {
        if (this.$refs[id]) {
          this.$refs[id][0].focus();
        }
      });
    },

    deleteAsset(asset) {
      const aIndex = this.assets.findIndex((a) => a.id === asset.id);
      if (aIndex > -1) {
        this.assets.splice(aIndex, 1);
      }
    },

    sourcePathChanged(path, asset) {
      asset.targetPath = path;
      // asset.targetPath = path.indexOf('/') > -1 ? path.substr(path.lastIndexOf('/') + 1) : path;
    },

    toggleEnableAllAssets(active, category) {
      this.assets.filter((a) => a.type === category.id).forEach((a) => a.enabled = active);
    },

    getAssetLabel(asset) {
      return this.str.ellipse(asset.sourcePath, 50);
    },
  },
};
</script>

<style scoped>

</style>
