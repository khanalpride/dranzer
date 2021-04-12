<template>
    <row>
      <column>
        <row>
          <column centered>
            <p class="text-primary">Content Wrapper</p>
          </column>
          <column size="6" offset="3" centered>
            <el-select class="el-sel-full-width"
                       filterable
                       clearable
                       :value="contentWrapperSelector"
                       @change="onContentWrapperChanged">
              <el-option :key="selector.selector" :label="str.ellipse(selector.selector, 60)"
                         :value="selector.selector" v-for="selector in baseLayoutSelectors">
                <template slot="default">
                        <span>
                            <span class="text-danger bold" v-if="selector.type === 'id'">ID</span>
                            <span class="text-primary bold" v-else>CL</span>
                            {{ str.ellipse(selector.selector, 60) }}
                        </span>
                  <span class="text-green m-l-20 pull-right bold">
                            {{ str.ellipse(selector.tagName, 30) }}
                        </span>
                </template>
              </el-option>
            </el-select>
          </column>
        </row>

        <row>
          <column>
            <separator />
          </column>
          <column centered>
            <p class="text-primary">
              Base Layout Selectors ({{ baseLayoutSelectors.length }})
              <span v-if="baseLayoutSelectors.length - uniqueBaseLayoutSelectors.length > 0">
                    (<strong>{{ baseLayoutSelectors.length - uniqueBaseLayoutSelectors.length }}</strong> Consumed)
                </span>
            </p>
          </column>
            <column size="6" offset="3" centered>
              <el-select class="el-sel-full-width"
                         filterable
                         clearable
                         v-model="selectedSelector"
                         @change="handleSelectorChanged">
                <el-option :key="selector.selector" :label="str.ellipse(selector.selector, 60)"
                           :value="selector.selector" v-for="selector in uniqueBaseLayoutSelectors">
                  <template slot="default">
                        <span>
                            <span class="text-danger bold" v-if="selector.type === 'id'">ID</span>
                            <span class="text-primary bold" v-else>CL</span>
                            {{ str.ellipse(selector.selector, 60) }}
                        </span>
                    <span class="text-green m-l-20 pull-right bold">
                            {{ str.ellipse(selector.tagName, 30) }}
                        </span>
                  </template>
                </el-option>
              </el-select>
            </column>

            <column push10 centered>
              <pg-check-box no-margin centered label="Exclude .row" v-model="ignoredClasses.row.enabled" @change="generateBaseLayoutSelectors" />
              <pg-check-box no-margin centered label="Exclude .col-*" v-model="ignoredClasses.col.enabled" @change="generateBaseLayoutSelectors" />
            </column>
        </row>
      </column>

        <column v-if="partials.length && !loading">
          <separator />
            <el-tree
                :data="nodes"
                node-key="id"
                default-expand-all
                :expand-on-click-node="true">
                  <span class="custom-tree-node" slot-scope="{ node, data }">
                      <span>
                        <a href="#" class="text-danger" v-if="data.id" @click.prevent="onDeletePartial(data)">
                          <i class="fa fa-close"></i>
                        </a>
                        <span class="m-l-5" v-html="data.label"></span>
                      </span>
                  </span>
            </el-tree>
        </column>
    </row>
</template>

<script>
import { debounce } from 'lodash';
import { parse } from 'node-html-parser';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import mutations from '@/mixins/mutations';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Separator from '@/components/Layout/Separator';

export default {
  name: 'PartialsManager',
  props: {
    layoutId: [String, Number],
    layoutName: [String],
    layoutPath: [String],
    persistedPartials: Array,
    baseLayoutContents: String,
  },
  mixins: [mutations],
  components: {
    Separator,
    PgCheckBox,
    Column,
    Row,
  },
  data() {
    return {
      loading: false,

      deleting: [],

      lazyParsePartials: null,

      partials: JSON.parse(JSON.stringify(this.persistedPartials || [])),

      selectedSelector: null,

      baseLayoutSelectors: [],

      ignoredClasses: {
        row: {
          enabled: true,
        },
        col: {
          enabled: true,
        },
      },

      contentWrapper: null,
    };
  },
  watch: {
    baseLayoutContents: {
      async handler(v) {
        this.baseLayoutSelectors = await this.getBaseLayoutSelectors(v);
      },
    },
    layoutPath: {
      handler() {
        this.$nextTick(() => {
          // this.lazyParsePartials();
        });
      },
    },
    persistedPartials: {
      handler(v) {
        this.partials = JSON.parse(JSON.stringify(v || []));
      },
      immediate: true,
    },
  },
  computed: {
    uniqueBaseLayoutSelectors() {
      return this.baseLayoutSelectors.filter((c) => !this.partials.find((p) => p.node.selector.trim() === c.selector.trim()));
    },

    nodes() {
      const path = `views/partials/${this.layoutName}`.split('/').reverse().join('/');

      const pathNodes = [];

      path.split('/').forEach((p) => {
        pathNodes.push({
          label: p,
          children: pathNodes.length
            ? [pathNodes[pathNodes.length - 1]]
            : this.partials.map((partial) => ({ label: this.getPartialHeading(partial), wrapper: partial.wrapper, ...partial })),
        });
      });

      return [pathNodes.reverse()[0]];
    },

    contentWrapperSelector() {
      const wrapperPartial = this.partials.find((p) => p.wrapper);

      if (this.contentWrapper) {
        return this.contentWrapper.selector;
      }

      return wrapperPartial ? wrapperPartial.node.selector : null;
    },
  },
  async created() {
    this.loading = true;
    this.lazyParsePartials = debounce((updatedPartial) => this.parsePartials(updatedPartial), 300);
    await this.generateBaseLayoutSelectors();
    await this.syncContentWrapper();
    this.loading = false;
  },
  methods: {
    async generateBaseLayoutSelectors() {
      this.baseLayoutSelectors = await this.getBaseLayoutSelectors(this.baseLayoutContents);
    },

    async syncContentWrapper() {
      const { data } = await this.mutation({ path: `ui/custom/content-wrapper/${this.layoutId}` });
      this.contentWrapper = data.value || this.contentWrapper;
    },

    async getBaseLayoutSelectors(baseLayoutContents) {
      const bodyTag = this.rgx.getFirstMatch('<body.*?</body>', baseLayoutContents);

      const tags = this.rgx.getMatches(/(?<=<)[a-zA-Z0-9-]+.*?>/, bodyTag);

      const selectors = [];

      const acceptedTags = ['body', 'div', 'section', 'header', 'main', 'nav', 'ul', 'ol'];

      tags.forEach((tag) => {
        const ignore = [];

        if (this.ignoredClasses.row.enabled) {
          ignore.push('row');
        }

        if (this.ignoredClasses.col.enabled) {
          ignore.push('col-');
        }

        const tagName = this.rgx.getFirstMatch(/[a-zA-Z0-9-]+.*?(?=\s)/, tag);
        const classSelector = this.rgx.getFirstMatch(/(?<=class=").*?(?=")/, tag);
        const idSelector = this.rgx.getFirstMatch(/(?<=id=").*?(?=")/, tag);

        if (!classSelector && !idSelector) {
          return false;
        }

        if (!tagName || !acceptedTags.includes(tagName.toLowerCase())) {
          return false;
        }

        const type = idSelector ? 'id' : 'class';

        const selector = type === 'id' ? idSelector : classSelector;

        // eslint-disable-next-line no-restricted-syntax
        for (const ign of ignore) {
          if (selector.toLowerCase().indexOf(ign.toLowerCase()) > -1) {
            return false;
          }
        }

        if (!selectors.find((s) => s.type === type && s.selector === selector)) {
          selectors.push({ type, tagName, selector });
        }

        return true;
      });

      return selectors;
    },

    handleSelectorChanged(selector) {
      this.selectedSelector = null;

      const node = this.baseLayoutSelectors.find((c) => c.selector === selector);

      if (!node) {
        return;
      }

      const name = this.rgx.getFirstMatch(/[a-zA-Z0-9_-]+/, node.selector) || 'unnamed_partial';

      let partial = this.partials.find((p) => p.node.selector === node.selector && p.node.tagName === node.tagName);

      if (!partial) {
        partial = {
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          name,
          node,
          wrapper: false,
        };

        partial.code = this.parsePartial(partial);

        this.partials.push(partial);

        this.$emit('updated', this.partials);

        this.persistPartial(partial);

        this.parsePartials();
      }
    },

    onContentWrapperChanged(selector) {
      const node = this.baseLayoutSelectors.find((b) => b.selector === selector);

      if (!node) {
        return;
      }

      this.contentWrapper = node;
      this.persistContentWrapper();
    },

    clearContentWrapper() {
      this.contentWrapper = null;
      this.persistContentWrapper();
    },

    persistContentWrapper(node) {
      const payload = {
        name: 'Content Wrapper',
        path: `ui/custom/content-wrapper/${this.layoutId}`,
        value: node || this.contentWrapper,
      };

      this.mutate(payload);
    },

    prepareSelectorForQuerying(selector, nodeType) {
      if (nodeType !== 'id') {
        selector = selector.replace(/\s+/ig, '.');

        if (selector.substr(0, 1) !== '.') {
          selector = `.${selector}`;
        }

        if (selector.substr(selector.length - 1, 1) === '.') {
          selector = selector.substr(0, selector.length - 1);
        }
      }

      if (nodeType === 'id' && selector.substr(0, 1) !== '#') {
        selector = `#${selector}`;
      }

      return selector;
    },

    parsePartial(partial) {
      const html = this.baseLayoutContents;

      // window.parse = parse;

      const dom = parse(html);

      const selector = this.prepareSelectorForQuerying(partial.node.selector, partial.node.type);

      // window.ddom = dom;

      const node = dom.querySelector(selector);

      if (!node || node.tagName.toLowerCase() !== partial.node.tagName.toLowerCase()) {
        return null;
      }

      let nodeHtml = node.outerHTML;

      this.partials.forEach((p) => {
        if (p.id === partial.id) {
          return false;
        }

        const partialDom = parse(nodeHtml);

        const partialSelector = this.prepareSelectorForQuerying(p.node.selector, p.node.type);

        const partialNode = partialDom.querySelector(partialSelector);

        if (partialNode && partialNode.tagName.toLowerCase() === p.node.tagName.toLowerCase()) {
          const partialNodeHtml = partialNode.outerHTML;
          const includePath = `partials.${this.layoutName}.${p.name}`;
          nodeHtml = nodeHtml.replace(partialNodeHtml, `\n@include('${includePath}')\n`);
        }

        return true;
      });

      return !nodeHtml || nodeHtml.trim() === '' ? null : nodeHtml;
    },

    persistPartial(partial) {
      const value = JSON.parse(JSON.stringify(partial));

      const payload = {
        name: 'Layout Partial',
        path: `ui/partials/${this.layoutId}/${partial.id}`,
        value,
      };

      this.mutate(payload);
    },

    handlePartialNameUpdated(newName, partial) {
      partial.name = newName;
      this.lazyParsePartials(partial);
    },

    parsePartials(updatedPartial) {
      this.partials.forEach((partial) => {
        const partialCode = partial.code;
        const code = this.parsePartial(partial);
        partial.code = code;
        // this.$set(partial, 'code', code);
        if (code !== partialCode || (updatedPartial ? partial.id === updatedPartial.id : false)) {
          this.persistPartial(partial);
        }
      });
    },

    async onDeletePartial(partial) {
      const partialIndex = this.partials.findIndex((p) => p.id === partial.id);
      if (partialIndex > -1) {
        this.deleting.push(partial.id);
        const { status } = await this.deleteMutation(`ui/partials/${this.layoutId}/${partial.id}`);
        this.deleting.splice(this.deleting.indexOf(partial.id), 1);
        if (status === 201 || status === 404) {
          this.partials.splice(partialIndex, 1);
          this.$emit('updated', this.partials);
          this.lazyParsePartials();
        }
      }
    },

    getPartialHeading(partial) {
      const type = partial.node.type === 'id' ? 'ID' : 'CL';
      const typeClass = type === 'ID' ? 'text-danger' : 'text-primary';
      return `<span class="${typeClass} bold">[${type}]</span> ${this.str.ellipse(`${partial.name}.blade.php`, 60)}`;
    },

    isDeletingPartial(partial) {
      return this.deleting.indexOf(partial.id) > -1;
    },

    onShow(e) {
      e.popper.style.width = '580px';
    },
  },
};
</script>

<style scoped>
.custom-tree-node {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-right: 8px;
}
</style>
