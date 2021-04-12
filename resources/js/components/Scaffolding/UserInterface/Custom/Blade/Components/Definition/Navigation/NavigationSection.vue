<template>
  <div>
    <row v-if="loading">
      <column size="4" offset="4">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column>
        <pg-input v-model="containerSelector"
                  placeholder="Navigation Container Selector (Must be a parent of ul tag)"
                  @input="buildNav" />
      </column>
      <column push10 v-if="nav.length">
        <el-cascader-panel v-model="links" :options="nav" @expand-change="onExpandChanged" @change="onSelectionChanged">
          <template slot-scope="{node, data}">
            <span v-if="data.enabled">{{ data.label }}</span>
            <span class="text-info hint-text" style="opacity:0.3;" v-else>{{ data.label }}</span>
          </template>
        </el-cascader-panel>
      </column>

      <column push10 :key="exp.id" v-for="exp in selectedNodes">
        <row>
          <column>
            <pg-check-box no-margin label="Enabled" v-model="exp.enabled" @change="onNodeStateUpdated($event, exp)" />
          </column>
          <column push10 size="10" class="m-l-30">
            <row>
              <column size="4">
                <form-input-title :centered="false" title="New Label" />
                <pg-input class="input-max-height" v-model="exp.label" @input="onNodeLabelUpdated(exp)" />
              </column>
              <column size="6" v-if="views && views.length && getNode(exp.id) && !getNode(exp.id).children">
                <form-input-title :centered="false" title="Associated View" />
                <el-select v-model="exp.view">
                  <el-option :key="view.id" :value="view.id" :label="`${view.name}.blade.php`" v-for="view in views" />
                </el-select>
              </column>
            </row>
          </column>
        </row>
      </column>
    </row>
  </div>
</template>

<script>
import { parse } from 'node-html-parser';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import PgInput from '@/components/Forms/PgInput';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'NavigationSection',
  mixins: [mutations],
  props: {
    layoutId: {
      required: true,
    },
    baseLayoutContents: {
      type: String,
      required: true,
    },
    section: {
      type: String,
      default: 'header',
    },
    views: Array,
  },
  components: {
    IndeterminateProgressBar,
    PgInput,
    FormInputTitle,
    PgCheckBox,
    Row,
    Column,
  },
  data() {
    return {
      loading: false,

      nav: [],

      links: [],

      containerSelector: '',

      expanded: [],

      selected: null,
    };
  },
  computed: {
    selectedNodes() {
      return this.selected || this.expanded;
    },
  },
  watch: {
    selectedNodes: {
      handler() {
        const payload = {
          name: 'Navigation Section',
          path: `ui/custom/navigation/section/${this.layoutId}/${this.section}`,
          value: {
            nav: this.nav,
            containerSelector: this.containerSelector,
          },
        };

        this.mutate(payload);
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;

    if (!this.nav.length) {
      this.buildNav();
    }
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: `ui/custom/navigation/section/${this.layoutId}/${this.section}` });
      this.nav = data.value || [];
    },
    buildNav() {
      const dom = parse(this.baseLayoutContents);

      let sectionContainer = null;

      if (this.containerSelector.trim() !== '') {
        sectionContainer = dom.querySelector(this.containerSelector);
      } else if (this.section === 'header') {
        sectionContainer = dom.querySelector('nav')
            || dom.querySelector('.nav')
            || dom.querySelector('.nav-bar')
            || dom.querySelector('header');
      } else {
        sectionContainer = dom.querySelector('footer')
            || dom.querySelector('.footer');
      }

      const nav = this.parseNavNode(sectionContainer);

      this.nav = nav.map((a) => ({
        id: a.id,
        value: a.value,
        label: a.label,
        tag: a.tag,
        selector: a.selector,
        attributes: a.attributes,
        enabled: true,
        children: a.children,
        action: {
          view: null,
        },
      })).filter((a) => a.label.trim() !== '');
    },

    parseNavNode(navNode) {
      if (!navNode) {
        return [];
      }

      const nodes = [];

      const menu = parse(navNode.innerHTML).querySelector('ul');

      if (menu) {
        const menuItems = menu.childNodes.filter((c) => c.rawTagName === 'li');
        menuItems.forEach((m) => {
          const parsed = this.parseMenuItem(m);
          if (parsed) {
            nodes.push(parsed);
          }
        });
      }

      return nodes;
    },

    parseMenuItem(menuItemNode) {
      const firstNode = menuItemNode.firstChild;

      if (!firstNode) {
        return null;
      }

      let item = menuItemNode.childNodes.find((c) => c.rawTagName === 'a');
      let subMenu = menuItemNode.querySelector('ul');

      if (subMenu) {
        subMenu = this.parseNavNode(menuItemNode);
      }

      if (!item) {
        item = menuItemNode.childNodes.find((c) => c.rawTagName === 'span') || {};
      }

      const children = (subMenu || []).filter((c) => c.label && c.label.trim() !== '');

      const classSelector = item.attributes.class ? `.${item.attributes.class.replace(/\s/g, '.')}` : '';

      // let parentSelector = this.buildParentSelector(menuItemNode, item);
      //
      // if (parentSelector.endsWith(' > ')) {
      //   parentSelector = parentSelector.substr(0, parentSelector.indexOf(' > '));
      // }

      const selector = `${menuItemNode.rawTagName}.${menuItemNode.attributes.class.replace(/\s/g, '.')} > ${item.rawTagName}${classSelector}`;

      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);

      return {
        id,
        enabled: true,
        tag: item.rawTagName,
        attributes: item.attributes,
        label: this.humanizeAnchorText(item.innerHTML),
        selector,
        value: {
          id,
          view: null,
          enabled: true,
          html: item.outerHTML,
          originalLabel: item.innerHTML,
          label: this.humanizeAnchorText(item.innerHTML),
        },
        children: children.length ? children : null,
      };
    },

    buildParentSelector(container, selector) {
      if (!container) {
        return selector;
      }

      let sel = '';

      const parent = this.buildItemParent(container);

      if (parent) {
        if (parent.parent) {
          sel += this.buildParentSelector(parent.parent, selector);
        }

        const classSelector = parent.attributes.class ? `.${parent.attributes.class.replace(/\s/g, '.')}` : '';

        sel += `${parent.tag}${classSelector} > `;
      }

      return sel;
    },

    buildItemParent(item) {
      if (!item) {
        return null;
      }

      return {
        tag: item.tag || item.rawTagName,
        attributes: item.attributes,
        parent: this.buildItemParent(item.parentNode),
      };
    },

    getNode(nodeId, container) {
      const links = container || this.nav;

      for (let i = 0; i < links.length; i += 1) {
        const link = links[i];

        if (link.id === nodeId) {
          return link;
        }

        if (!link.children) {
          // eslint-disable-next-line no-continue
          continue;
        }

        const node = this.getNode(nodeId, link.children);
        if (node) {
          return node;
        }
      }

      return null;
    },

    humanizeAnchorText(text) {
      if (!text || text.trim() === '') {
        return '';
      }

      return text.replace(/(<.*?>.*?<\/.*?>|<.*?>)/isg, '').trim();
    },

    onExpandChanged(e) {
      this.links = [];
      this.selected = null;
      this.expanded = e;
    },

    onSelectionChanged(e) {
      this.selected = e;
    },

    onNodeLabelUpdated(exp) {
      const node = this.getNode(exp.id);

      if (!node) {
        return;
      }

      node.label = exp.label;
    },

    onNodeStateUpdated(active, exp) {
      const node = this.getNode(exp.id);

      if (!node) {
        return;
      }

      node.enabled = active;
    },
  },
};
</script>

<style scoped>

</style>
