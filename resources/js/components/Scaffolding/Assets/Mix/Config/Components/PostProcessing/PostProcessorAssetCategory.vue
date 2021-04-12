<template>
    <row>
        <column v-if="invalidPaths.length">
          <p class="text-danger small" v-tippy content="If your uploaded template "><i
            class="fa fa-exclamation-triangle"></i> {{ invalidPaths.length }} Invalid Asset
            {{ invalidPaths.length === 1 ? 'Path' : 'Paths' }}. This is most likely an issue with the mis-configured base
            paths defined above.</p>
        </column>
        <column>
          <p class="text-danger small" :key="path.extracted" v-for="path in invalidPaths">
            <span class="text-black bold">{{ fs.fn(path.extracted) }}</span> could not be resolved as <span
            class="bold">{{ path.resolved }}</span>
          </p>
        </column>
        <column :push10="index > 0" :key="split.id" v-for="(split, index) in splits">
            <content-card :heading="split.outputPath" v-if="split.assets.length">
                <row>
                    <column>
                        <p class="no-margin text-complete small p-l-5 p-b-5"><i class="fa fa-sort"></i> Drag to sort</p>
                    </column>

                    <column>
                        <draggable v-model="split.assets">
                          <div :key="asset.id" v-for="asset in split.assets">
                            <pg-check-box v-model="asset.enabled" :label="asset.path"
                                          @change="handleAssetSplitStateChanged($event, asset, split)"/>
                          </div>
                        </draggable>
                    </column>
                </row>
            </content-card>
        </column>

        <column push10 v-if="nonSplitted.length">
            <content-card :heading="`Ungrouped ${categoryHeading} (${nonSplitted.length})`">
                <row>
                    <column>
                        <pg-check-box :value="nonSplitted.length === checked.length" @change="toggleCheckAllAvailableAssets" />
                    </column>
                    <column :key="asset.id" v-for="asset in nonSplitted">
                        <pg-check-box v-model="asset.enabled" :label="asset.asset" v-if="!isSplitted(asset.asset)" />
                    </column>

                    <column push5 v-if="checked.length">
                        <content-card :heading="`Group ${checked.length} ${splittableCardHeading}`">
                            <row>
                                <column centered>
                                    <p class="text-primary">Output Filename</p>
                                </column>
                                <column push10 size="6" offset="3">
                                    <el-select class="el-sel-full-width"
                                               filterable
                                               allow-create
                                               v-tippy="{placement: 'left', distance: 15}"
                                               content="Select or type in to create a new output filename"
                                               v-model="checkedTarget"
                                               @change="createNewSplit">
                                        <el-option :key="split.id" :label="split.outputPath" :value="split.id" v-for="split in splits" />
                                      <el-option value="app" :label="`${category === 'script' ? 'app.js' : 'app.css'}`" v-if="!hasAppPath" />
                                    </el-select>
                                </column>
                            </row>
                        </content-card>
                    </column>
                </row>
            </content-card>
        </column>
    </row>
</template>

<script>
import Draggable from 'vuedraggable';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ContentCard from '@/components/Cards/ContentCard';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';

export default {
  name: 'PostProcessorAssetCategory',
  props: {
    mixPaths: {},
    persistedAssets: Array,
    groups: Array,
    outputExtension: String,
    categoryHeading: String,
    category: String,
    prependSeparator: Boolean,
  },
  components: {
    PgCheckBox, ContentCard, Column, Row, Draggable,
  },
  data() {
    return {
      assets: JSON.parse(JSON.stringify(this.persistedAssets || [])),

      splits: this.groups || [],

      checkedTarget: null,
    };
  },
  computed: {
    hasAppPath() {
      const s = this.splits.find((sp) => (sp.outputPath === (this.category === 'script' ? 'app.js' : 'app.css')));
      return s !== undefined;
    },

    splittableCardHeading() {
      return this.checked.length === 1 ? this.str.singular(this.categoryHeading) : this.str.pluralize(this.categoryHeading);
    },

    nonSplitted() {
      return this.assets.filter((s) => !this.isSplitted(s.asset) && this.isRelativePath(s.asset));
    },

    checked() {
      return this.assets.filter((s) => !this.isSplitted(s.asset) && s.enabled);
    },

    invalidPaths() {
      const paths = this.mixPaths;

      const { templatePath } = paths;

      const invalidPaths = [];

      // eslint-disable-next-line consistent-return
      this.assets.forEach((asset) => {
        const actualPath = asset.asset;

        if (!this.isRelativePath(actualPath)) {
          return false;
        }

        if (!actualPath) {
          invalidPaths.push({
            extracted: asset.asset,
            resolved: asset.asset,
          });
          return false;
        }

        let resolved = actualPath.replace(templatePath, '');

        if (resolved.startsWith('/')) {
          resolved = resolved.substr(1);
        }

        if (asset.asset !== resolved) {
          invalidPaths.push({
            extracted: asset.asset,
            resolved,
          });
        }
      });

      return invalidPaths;
    },
  },
  created() {
    const vendorAssets = [];

    this.assets.forEach((asset) => {
      if (this.isVendorAsset(asset.asset)) {
        vendorAssets.push(asset.asset);
      }
      asset.enabled = false;
    });

    this.splitAssets('vendor', vendorAssets);
  },
  methods: {
    isSplitted(assetPath) {
      return this.splits.find((s) => s.assets.find((a) => a.path === assetPath));
    },

    splitAssets(outputPath, assets) {
      outputPath = outputPath.trim();

      if (!outputPath || outputPath === '') {
        return;
      }

      if (!outputPath.endsWith(`.${this.outputExtension}`)) {
        outputPath = `${outputPath}.${this.outputExtension}`;
      }

      if (this.splits.find((s) => s.outputPath === outputPath)) {
        return;
      }

      this.splits.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        outputPath,
        assets: assets.map((asset) => ({
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          path: asset,
          enabled: true,
        })),
        category: this.category,
      });
    },

    createNewSplit() {
      const split = this.splits.find((s) => s.id === this.checkedTarget);

      if (split && !split.assets.find((a) => this.checked.find((s) => s.asset === a.path))) {
        split.assets = split.assets.concat(this.checked.map((s) => ({
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          path: s.asset,
          enabled: true,
        })));
      } else {
        this.splitAssets(this.checkedTarget, this.checked.map((s) => s.asset));
      }

      this.$emit('grouping-changed', this.splits);

      this.checkedTarget = null;
    },

    handleAssetSplitStateChanged(checked, asset, split) {
      const splittedIndex = this.splits.findIndex((s) => split.outputPath === s.outputPath && split.category === s.category);

      if (splittedIndex > -1) {
        const assetIndex = split.assets.findIndex((a) => a.path === asset.path);
        if (assetIndex > -1) {
          split.assets.splice(assetIndex, 1);

          this.$nextTick(() => {
            const nonSplittedAsset = this.assets.find((s) => s.asset === asset.path);
            if (nonSplittedAsset) {
              nonSplittedAsset.enabled = false;
              this.$emit('grouping-changed', this.splits);
            }
          });
        }
      }
    },

    toggleCheckAllAvailableAssets(checkAll) {
      this.nonSplitted.forEach((s) => s.enabled = checkAll);
    },

    isVendorAsset(asset) {
      if (!asset || asset.trim() === '') {
        return false;
      }

      // noinspection SpellCheckingInspection
      const libs = [
        'pace', 'jquery', 'modernizr', 'popper', 'bootstrap', 'select2', 'classie', 'switchery', 'nvd3', 'd3', 'mapplic',
        'hammer', 'rickshaw', 'sparkline', 'skycons', 'html5shiv', 'respond', 'font-awesome',
      ];

      const assetDir = this.fs.dir(asset.toLowerCase().trim());
      const assetFilename = this.fs.fnNoExt(asset.toLowerCase().trim().replace('.min', ''));
      return libs.find((lib) => assetFilename.indexOf(lib) > -1) || libs.find((lib) => assetDir.indexOf(lib) > -1);
    },

    isRelativePath(path) {
      return !path ? false : (!path.startsWith('http') && !path.startsWith('//'));
    },
  },
};
</script>

<style scoped>

</style>
