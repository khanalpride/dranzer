<template>
  <row>
    <column>
      <pg-check-box no-margin :value="1" :label="view.name" @change="onViewStateChanged($event, view)" />
    </column>

    <column push10 size="11" class="m-l-30">
      <row>
        <column size="3">
          <form-input-title :centered="false" title="Route URI" />
          <pg-input class="input-max-height" v-model="view.uri" />
        </column>
        <column size="4">
          <form-input-title :centered="false" title="View Controller" />
          <simple-select full-width
                         v-model="selectedController"
                         filterable
                         clearable
                         :entities="controllers"
                         @change="onControllerUpdated">
            <template slot-scope="{ entity }">
              <el-option :key="entity.id"
                         :label="entity.name"
                         :value="entity.id" />
            </template>
          </simple-select>
        </column>
        <column size="5">
          <form-input-title :centered="false" title="Layout File" />
          <simple-select full-width
                         filterable
                         clearable
                         :value="layoutFilePath"
                         :entities="themeFiles"
                         @change="onLayoutFileUpdated">
            <template slot-scope="{ entity }">
              <el-option :key="entity.path" :label="str.ellipse(fs.fn(entity.path), 30)" :value="entity.path">
                <template slot="default">
                        <span class="m-r-20">
                            <i class="fa fa-html5"/>
                            {{ str.ellipse(fs.fn(entity.path), 30) }}
                        </span>
                  <span class="pull-right"
                        v-tippy="{placement: 'top', distance: 10, onShow: () => fs.dir(entity.path).length > 30}"
                        :content="`${fs.sep()}${fs.dir(entity.path)}`">
                                <i class="fa fa-folder"/>
                                {{ fs.sep() }}{{ str.ellipse(fs.dir(entity.path), 30) }}
                        </span>
                </template>
              </el-option>
            </template>
          </simple-select>
        </column>
      </row>
    </column>
    <column push10 size="11" class="m-l-30">
      <pg-check-box v-model="view.customContentWrapper" no-margin label="This page uses a different Content Wrapper" />
    </column>

    <column push10 size="11" class="m-l-30" v-if="view.customContentWrapper">
      <pg-input placeholder="Custom content wrapper selector (e.g. #wrapper or .page-container)..." v-model="view.contentWrapper" />
    </column>
  </row>
</template>

<script>
import sharedMutations from '@/mixins/shared_mutations';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import mutations from '@/mixins/mutations';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import PgInput from '@/components/Forms/PgInput';
import SimpleSelect from '@/components/Select/SimpleSelect';

export default {
  name: 'BladeView',
  props: {
    layout: Object,
    persistedView: {},
    indexFile: {},
    themeFiles: Array,
    controllers: Array,
  },
  mixins: [sharedMutations, mutations],
  components: {
    SimpleSelect,
    PgInput,
    FormInputTitle,
    PgCheckBox,
    Row,
    Column,
  },
  data() {
    return {
      selectedController: (this.persistedView || {}).controller,

      view: (this.persistedView || {}),

      layoutFilePath: null,
    };
  },
  watch: {
    view: {
      handler() {
        this.broadcastUpdate();
      },
      deep: true,
    },
  },
  async created() {
    if (!this.view.layoutFile && this.themeFiles && this.themeFiles.length) {
      let layoutFile = this.getFile(this.view.name);

      if (!layoutFile) {
        layoutFile = this.getFile(this.view.name, true);
      }

      if (layoutFile) {
        this.onLayoutFileUpdated(layoutFile.path);
      }
    }

    if (!this.layoutFilePath && this.view && this.view.layoutFile) {
      if (this.view.layoutFile) {
        this.onLayoutFileUpdated(this.view.layoutFile.path);
      }
    }
  },
  methods: {
    getHeading() {
      let dir = this.fs.dir(this.layout.path);

      if (!dir) {
        dir = 'pages/';
      }

      dir = !dir ? 'pages/' : `pages/${dir}/`;

      return `
<span class="text-info hint-text">${this.str.ellipse(dir, 30)}${dir.length > 30 ? '/' : ''}</span>
<span class="text-complete">${this.str.ellipse(this.view.name, 30)}.blade.php</span>
`;
    },

    onControllerUpdated(controllerId) {
      this.view.controller = controllerId;
      this.broadcastUpdate();
    },

    onLayoutFileUpdated(layoutFilePath) {
      this.layoutFilePath = layoutFilePath;
      this.view.layoutFile = this.themeFiles.find((f) => f.path === layoutFilePath);
      this.broadcastUpdate();
    },

    onViewStateChanged(active) {
      if (!active) {
        this.$emit('delete');
      }
    },

    broadcastUpdate() {
      this.$emit('updated', this.view);
    },

    getFile(filename, partialMatch = false) {
      let separatorCount = Number.MAX_SAFE_INTEGER;

      let file = null;

      let hasFile = false;

      this.themeFiles.forEach((themeFile) => {
        const name = this.fs.fnNoExt(themeFile.path);
        const count = this.str.substrCount(themeFile.path, this.fs.sep());

        const match = partialMatch ? name.indexOf(filename) > -1 : name === filename;

        if (count <= separatorCount) {
          separatorCount = count;

          if (match) {
            file = themeFile;
            hasFile = true;
          }

          return;
        }

        if (match && !hasFile) {
          file = themeFile;
        }
      });

      return file;
    },
  },
};
</script>

<style scoped>

</style>
