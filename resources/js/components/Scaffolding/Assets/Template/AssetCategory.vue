<template>
    <div>
        <row>
            <column centered v-if="filters.length">
                <row>
                    <column>
                        <p class="text-green no-margin">
                          <span>{{ filters.length }} {{
                              filters.length === 1 ? filterCategory : filterCategoryPluralized
                            }}<span v-if="!allChecked">, <span class="text-danger">{{
                                activeAssets.length
                              }} Active</span></span></span>
                        </p>
                    </column>
                    <column>
                        <separator />
                    </column>
                </row>
            </column>
            <column v-if="filters.length > 1">
                <row>
                    <column size="10">
                        <pg-check-box :value="allChecked" @change="handleCheckAllToggled" />
                    </column>
                    <column size="2" push10>
                        <a href="#" class="text-danger pull-right" @click.prevent="$emit('delete-all')"><i class="fa fa-close"></i></a>
                    </column>
                </row>
            </column>
            <column :push10="index > 0" :key="f.id" v-for="(f, index) in showAll ? filters : filters.slice(0, 10)">
                <row>
                    <column size="10">
                        <pg-check-box v-model="f.enabled" :label="getFilterLabel(f)" @change="handleAssetState(f, $event)" />
                    </column>

                    <column size="2" push10>
                        <a href="#" class="text-danger pull-right" @click.prevent="$emit('delete', f)"><i class="fa fa-close"></i></a>
                    </column>
                </row>
            </column>

            <column v-if="filters.length > 10 && !showAll">
                <row>
                    <column>
                        <separator />
                    </column>

                    <column>
                        <p class="text-complete">
                          <i class="fa fa-info"></i>
                          {{ filters.length - 10 }}
                          more
                          {{ (filters.length - 10 > 1 ? pluralizedAssetName.toLowerCase() : filterCategory.toLowerCase()) }}
                          <a href="#" class="m-l-10 small link" @click.prevent="showAll = true">Show All</a>
                        </p>
                    </column>
                </row>
            </column>
        </row>
    </div>
</template>

<script>
import pluralize from 'pluralize';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Separator from '@/components/Layout/Separator';
import StringHelpers from '@/helpers/string_helpers';

export default {
  name: 'AssetCategory',
  props: {
    linkExtractionAttribute: {
      type: [String, Array],
      default: 'href',
    },
    linkExtractionTag: String,
    filterCategory: String,
    filters: Array,
    matchAllowedCallback: Function,
  },
  components: {
    Separator, PgCheckBox, Column, Row,
  },
  data() {
    return {
      prefix: '',
      showAll: false,
    };
  },
  computed: {
    allChecked() {
      return this.activeAssets.length === this.filters.length;
    },

    activeAssets() {
      return this.filters.filter((f) => f.enabled);
    },

    filterCategoryPluralized() {
      return pluralize(this.filterCategory);
    },

    pluralizedAssetName() {
      if (this.filterCategory === 'Stylesheet') {
        return 'Stylesheets';
      }

      if (this.filterCategory === 'Script') {
        return 'Scripts';
      }

      if (this.filterCategory === 'Image') {
        return 'Images';
      }

      if (this.filterCategory === 'Video') {
        return 'Videos';
      }

      return null;
    },
  },
  methods: {
    getFilterLabel(filter) {
      return StringHelpers.ellipse(filter.asset, 65);
    },

    handleLinksPasted(text) {
      this.$emit('links-pasted', { prefix: this.prefix, text });
    },

    handleAssetState(filter, checked) {
      this.$emit('state-changed', { asset: filter, checked });
    },

    handleCheckAllToggled(checked) {
      this.$emit('check-all-toggled', checked);
    },
  },
};
</script>

<style scoped>

</style>
