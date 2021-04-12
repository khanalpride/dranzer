<template>
    <row>
        <column size="8" offset="2" push5>
            <el-select class="el-sel-full-width"
                       filterable
                       clearable
                       v-model="baseLayoutFile"
                       @change="handleBaseViewFileChanged">
                <el-option :key="file.path" :label="str.ellipse(fs.fn(file.path), 30)" :value="file.path"
                           v-for="file in themeFiles">
                    <template slot="default">
                        <span class="m-r-20">
                            <i class="fa fa-html5"/>
                            {{ str.ellipse(fs.fn(file.path), 30) }}
                        </span>
                        <span class="pull-right"
                              v-tippy="{placement: 'top', distance: 10, onShow: () => fs.dir(file.path).length > 30}"
                              :content="`${fs.sep()}${fs.dir(file.path)}`">
                                <i class="fa fa-folder"/>
                                {{ fs.sep() }}{{ str.ellipse(fs.dir(file.path), 30) }}
                        </span>
                    </template>
                </el-option>
            </el-select>
        </column>
    </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import mutations from '@/mixins/mutations';

export default {
  name: 'BaseViewManager',
  props: {
    themeFiles: Array,
    indexFile: Object,
  },
  mixins: [mutations],
  components: { Column, Row },
  data() {
    return {
      baseLayoutFile: null,
    };
  },
  watch: {
    indexFile: {
      handler(v) {
        this.baseLayoutFile = v && v.path ? v.path : null;
      },
      immediate: true,
    },
  },
  created() {
    if (this.indexFile) {
      this.baseLayoutFile = this.indexFile.path;
    }
  },
  methods: {
    async handleBaseViewFileChanged(path) {
      const file = this.themeFiles.find((f) => f.path === path);
      this.$emit('file-changed', file);
    },
  },
};
</script>

<style scoped>

</style>
