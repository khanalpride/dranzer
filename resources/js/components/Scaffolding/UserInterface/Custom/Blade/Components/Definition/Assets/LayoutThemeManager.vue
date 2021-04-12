<template>
    <div>
        <row v-if="loading">
            <column centered size="4" offset="4">
                <indeterminate-progress-bar />
            </column>
        </row>
        <row v-else>
            <column>
                <fill-in-modal ref="themeUploadProgressModal">
                    <row>
                        <column>
                            <p>Your template / theme is being uploaded...</p>
                        </column>

                        <column>
                            <progress-bar :percentage="uploadPercentage" />
                        </column>
                    </row>
                </fill-in-modal>
            </column>

<!--            <column size="8" offset="2">-->
<!--                <p class="text-primary no-margin"><i class="fa fa-info"></i> Use the file uploader below to upload the-->
<!--                    template / theme for your layout. Only zip files are supported and must not exceed 100MB in size.</p>-->
<!--            </column>-->

            <column centered push5 v-if="!themeUploaded">
                <el-upload
                    drag
                    action="/assets/upload"
                    accept="application/zip"
                    :data="{key: layoutId, mode: 'replace', module: 'layout', 'projectId': project.uuid, '_token': env.csrfToken}"
                    :show-file-list="false"
                    :before-upload="handleBeforeFileUpload"
                    :on-progress="handleUploadProgress"
                    :on-change="handleUploadStateChange"
                    :on-success="handleUploadSuccess">
                    <i class="el-icon-upload"></i>
                    <div class="el-upload__text">Drop file here or <em>click to upload</em></div>
                    <div class="el-upload__tip" slot="tip">Allowed File Type: <strong class="text-green">zip</strong> - Max
                        Size: <strong class="text-green">100MB</strong></div>
                </el-upload>
            </column>

            <column v-if="themeFilename && themeUploaded">
                <row v-if="!removingThemeFile">
                    <column centered>
                        <p class="text-complete bold">Uploaded Template / Theme</p>
                    </column>

                    <column>
                    <span class="text-green">
                        <i class="fa fa-check"></i> {{ getThemeFilename() || 'Theme File' }}
                        <a href="#" class="text-danger link m-l-10"
                           v-tippy="{placement: 'right', distance: 15}"
                           content="Remove Uploaded Template / Theme"
                           @click.prevent="removeThemeFile">
                            <i class="fa fa-close"></i>
                        </a>
                    </span>
                    </column>

<!--                    <column>-->
<!--                        <pg-check-box :value="isMainTheme" centered-->
<!--                                      :label="`For layouts that do not have a template / theme, use ${getThemeFilename()}`"-->
<!--                                      @change="persistMainTheme"/>-->
<!--                    </column>-->
                </row>

                <row v-else>
                    <column>
                        <separator/>
                    </column>

                    <column>
                    <span class="text-danger">
                        Removing {{ getThemeFilename() || 'Theme File' }}...
                    </span>
                    </column>
                </row>
            </column>

<!--            <column v-if="mainTheme && mainTheme.layoutId && mainTheme.layoutId !== layoutId && !themeFilename">-->
<!--                <column>-->
<!--                    <separator/>-->
<!--                </column>-->

<!--                <column centered>-->
<!--                    <p class="text-complete bold">Using Template / Theme uploaded in <strong>-->
<!--                        {{ mainTheme.layoutName }}.blade.php</strong></p>-->
<!--                </column>-->

<!--                <column>-->
<!--                    <span class="text-green">-->
<!--                        <i class="fa fa-check"></i>-->
<!--                        {{ getThemeFilename(mainTheme.filename) }}-->
<!--                    </span>-->
<!--                </column>-->
<!--            </column>-->
        </row>
    </div>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';

import project from '@/mixins/project';
import mutations from '@/mixins/mutations';
import StringHelpers from '@/helpers/string_helpers';
import FillInModal from '@/components/Modals/FillInModal';
import ProgressBar from '@/components/Progress/ProgressBar';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import jsZip from 'jszip';

const { axios } = window;

export default {
  name: 'LayoutThemeManager',
  mixins: [project, mutations],
  props: {
    layoutId: [String, Number],
    layoutName: String,
  },
  components: {
    IndeterminateProgressBar, ProgressBar, FillInModal, PgCheckBox, Separator, Column, Row,
  },
  data() {
    return {
      loading: false,

      zipResource: null,

      themeFile: null,
      themeUploaded: false,
      themeFilename: null,
      removingThemeFile: false,
      uploadPercentage: 0,

      maxAllowedThemeSizeInMB: 100,

      resourcePath: 'resources',
      templatePath: 'template',
      outputPath: 'public',
    };
  },
  computed: {
    // isMainTheme() {
    //   return !this.mainTheme ? false : this.mainTheme.layoutId === this.layoutId;
    // },

    // mainTheme() {
    //   const mutation = this.mutations.find((m) => m.path === 'assets/template/theme/main');
    //   return mutation && mutation.value && mutation.value.layoutId ? mutation.value : null;
    // },

    mixPaths() {
      const resourcePath = this.resourcePath.trim() === '' ? 'resources' : this.resourcePath.trim();
      const templatePath = this.templatePath.trim();
      const outputPath = this.outputPath.trim() === '' ? 'public' : this.outputPath.trim();

      return {
        resourcePath,
        templatePath,
        outputPath,
      };
    },
  },
  async created() {
    this.loading = true;
    // this.registerMutable('Main Theme', 'assets/template/theme/main', () => {});
    await this.syncMixPaths();
    await this.syncThemeFile();
    if (this.themeFilename) {
      this.$emit('has-theme');
    }
    this.loading = false;
  },
  methods: {
    async syncMixPaths() {
      const { data } = await this.mutation({ path: `assets/mix/paths/${this.layoutId}` });
      const paths = data.value || {};

      this.resourcePath = paths.resourcePath || this.resourcePath;
      this.templatePath = paths.templatePath || this.templatePath;
      this.outputPath = paths.outputPath || this.outputPath;
    },

    async syncThemeFile() {
      const { status, data } = await axios.post('/assets', { key: this.layoutId, module: 'layout', projectId: this.project.uuid });

      if (status === 200) {
        this.themeUploaded = data.asset.original_filename;
        this.themeFilename = data.asset.original_filename;
      }
    },

    async loadThemeFile() {
      return !this.themeFile
        ? new Promise((e) => e(false))
        : jsZip.loadAsync(this.themeFile);
    },

    async getThemeFileResponse(processFiles = true) {
      const response = await this.loadThemeFile();
      const files = response ? response.files : null;

      const fileList = [];

      if (files && processFiles) {
        // eslint-disable-next-line no-restricted-syntax
        for (const file in files) {
          // eslint-disable-next-line no-prototype-builtins
          if (!files.hasOwnProperty(file)) {
            // eslint-disable-next-line no-continue
            continue;
          }

          const props = files[file];

          if (props.dir) {
            // eslint-disable-next-line no-continue
            continue;
          }

          fileList.push({ path: props.name, props: { name: props.name, isDir: props.dir } });
        }
      }

      return { files: fileList, response };
    },

    getThemeFilename(themeFilename) {
      return StringHelpers.ellipse(themeFilename || this.themeFilename, 50);
    },

    async processTheme() {
      const { files, response } = await this.getThemeFileResponse();

      this.templatePath = this.fs.fnNoExt(this.themeFilename);

      const payload = {
        name: 'Mix Template Paths',
        path: `assets/mix/paths/${this.layoutId}`,
        value: this.mixPaths,
      };

      this.mutate(payload);

      this.zipResource = this.response;

      const htmlFiles = [];

      this.indexFile = null;

      const assetPaths = [];

      files.forEach((file) => {
        const ext = this.fs.ext(file.path);

        if (ext) {
          const lcExt = ext.toLowerCase();
          if (
            (lcExt.length >= 3 && lcExt.substr(0, 3) === '.js')
            || (lcExt.length >= 4 && lcExt.substr(0, 4) === '.css')
            || (lcExt.length >= 5 && lcExt.substr(0, 5) === '.woff')
            || (lcExt.length >= 5 && lcExt.substr(0, 6) === '.woff2')
            || (lcExt.length >= 4 && lcExt.substr(0, 4) === '.ttf')
            || (lcExt.length >= 4 && lcExt.substr(0, 4) === '.otf')
            || (lcExt.length >= 4 && lcExt.substr(0, 4) === '.eot')
          ) {
            assetPaths.push(file.path);
          }
        }

        if (ext !== '.html') {
          return false;
        }

        htmlFiles.push(file);

        return true;
      });

      let separatorCount = Number.MAX_SAFE_INTEGER;

      let indexFile = null;
      let baseLayoutFile = null;

      let hasIndexFile = false;

      htmlFiles.forEach((file) => {
        const name = this.fs.fnNoExt(file.path);
        const count = this.str.substrCount(file.path, this.fs.sep());

        if (count <= separatorCount) {
          baseLayoutFile = file;
          separatorCount = count;

          if (name.toLowerCase() === 'index') {
            indexFile = file;
            hasIndexFile = true;
          }

          return;
        }

        if (name.toLowerCase() === 'index' && !hasIndexFile) {
          indexFile = file;
        }
      });

      if (indexFile) {
        baseLayoutFile = indexFile;
      }

      this.$emit('processed', {
        files: htmlFiles, baseLayoutFile, response, assetPaths,
      });
    },

    async removeThemeFile() {
      this.removingThemeFile = true;

      const { status } = await axios.post('/assets/delete', { key: this.layoutId, module: 'layout', projectId: this.project.uuid });

      this.themeUploaded = false;

      this.themeFilename = null;

      this.$emit('removing');

      if (status === 200) {
        this.$emit('removed');

        const { data } = await this.mutation({ path: 'assets/template/theme/main' });

        if (data.value && data.value.layoutId && data.value.layoutId === this.layoutId) {
          const payload = {
            name: 'Main Theme',
            path: 'assets/template/theme/main',
            value: null,
          };

          this.mutate(payload);
        }
      }
      this.removingThemeFile = false;
    },

    persistMainTheme(checked) {
      const name = 'Main Theme';
      const path = 'assets/template/theme/main';

      const value = checked ? {
        layoutId: this.layoutId,
        filename: this.themeFilename,
        layoutName: this.layoutName,
      } : null;

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },

    handleBeforeFileUpload(file) {
      if (!file.size || !file.type) {
        return false;
      }

      if (file.type !== 'application/zip') {
        return false;
      }

      const sizeInMB = file.size / 1024 / 1024;

      const valid = Math.ceil(sizeInMB) <= this.maxAllowedThemeSizeInMB;

      if (valid) {
        this.themeFilename = file.name;
        this.themeFile = file;
        this.$refs.themeUploadProgressModal.show();
      }

      return valid;
    },

    handleUploadStateChange(e) {
      if (e.status === 'success') {
        setTimeout(() => {
          if (this.$refs.themeUploadProgressModal) {
            this.$refs.themeUploadProgressModal.hide();
          }
        }, 1000);
      }
    },

    async handleUploadSuccess() {
      this.themeUploaded = true;
      this.$refs.themeUploadProgressModal.hide();
      this.$emit('processing', true);

      this.processing = true;
      await this.processTheme();
      this.processing = false;

      this.$emit('processing', false);
    },

    handleUploadProgress(e) {
      this.uploadPercentage = e.percent;
    },
  },
};
</script>

<style scoped>

</style>
