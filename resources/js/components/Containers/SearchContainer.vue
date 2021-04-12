<template>
    <div>
        <row>
            <column>
                <pg-input ref="searchInput"
                          v-model="searchTerm"
                          :disabled="!mounted"
                          :placeholder="pHolder"
                          @keyup.13.native="showHighlightedModule"
                          @keyup.esc.native="cancelSearch"
                          @blur.native="focusLost"
                          @focus.native="gainedFocus"
                />
            </column>
        </row>
        <row push10>
            <column>
                <slide-y-up-transition group :duration="500">
                    <row :key="module.title" v-for="(module, index) in filtered">
                        <column>
                            <p class="bold text-primary m-l-5 no-margin"
                               :style="[index > 0 ? { paddingTop: '9px' } : {}]">
                                <span v-tippy="{ placement: 'bottom', interactive: true, onShow: () => module.tooltip !== undefined}"
                                      :content="module.tooltip ? getTooltipText(module.tooltip) : ''"
                                      v-html="module.title"/>
                                <i class="fa fa-arrow-right small m-l-10"/>
                                <span v-if="module.children">
                                    <span v-for="(child, index) in module.children" :key="child.title"
                                          :class="{'m-l-10': index === 0, 'm-l-5': index > 0}">
                                        <span :class="{'text-primary': child.children, 'text-info': !child.children}">
                                            <a v-tippy="{ placement: 'bottom', interactive: true, onShow: () => child.tooltip !== undefined }"
                                               href="#"
                                               class="bold text-info module-link"
                                               :content="child.tooltip ? getTooltipText(child.tooltip) : ''"
                                               @click.prevent="showModule(child)"
                                               v-html="child.title"
                                               v-if="!child.children"/>
                                            <span class="m-l-5 dot-separator"
                                                  v-if="index !== module.children.length - 1 && !child.children">.</span>
                                            <span v-if="child.children" v-html="child.title"/>
                                        </span>
                                        <i v-if="child.children" class="fa fa-arrow-right small m-l-10 text-primary"/>
                                        <span v-for="(c, i) in child.children || []" :key="c.title">
                                          <span class="m-l-15 text-info">
                                            <span v-html="c.title"/>
                                            <span v-if="i !== child.children.length - 1"
                                                  class="m-l-5 dot-separator">.</span>
                                          </span>
                                        </span>
                                    </span>
                                </span>
                            </p>
                        </column>
                    </row>
                </slide-y-up-transition>
            </column>
        </row>
    </div>
</template>

<!--suppress NpmUsedModulesInstalled -->
<script>
import { SlideYUpTransition } from 'vue2-transitions';

import categories from '@/data/search/modules';

import Row from '@/components/Layout/Grid/Row';
import PgInput from '@/components/Forms/PgInput';
import Column from '@/components/Layout/Grid/Column';

export default {
  name: 'SearchContainer',
  components: {
    Column, Row, PgInput, SlideYUpTransition,
  },
  props: {
    placeholder: {
      type: String,
      default: 'What would you like to add or configure?',
    },
  },
  data() {
    return {
      searchTerm: '',
      categories: [],
      activeModule: null,
      highlighted: null,
      lastActivatedModuleKey: null,
      highlightedCategory: null,
      configurable: [],
      mounted: false,
      pHolder: null,
      initialFocusComplete: false,
    };
  },
  computed: {
    filtered() {
      const filtered = [];

      const highlightColorClass = 'text-complete';

      const query = this.searchTerm.trim();

      const mappedCategories = this.categories;

      if (query === '') {
        Object.keys(mappedCategories).forEach((key) => {
          const module = mappedCategories[key];

          // Reset the title of the category and it's children
          module.title = module.originalTitle;

          module.children.forEach((child) => {
            child.title = child.originalTitle;
          });
        });

        return [];
      }

      const highlight = (searchTerm, colorClass, title) => title.replace(new RegExp(`(${searchTerm})`, 'i'), `<u class="${colorClass}">$1</u>`);

      Object.keys(mappedCategories).forEach((key) => {
        const module = mappedCategories[key];

        // Reset the title of the category and it's children
        module.title = module.originalTitle;

        module.children.forEach((child) => {
          child.title = child.originalTitle;
        });

        let childMatched = false;

        // Search the category children for matches
        module.children.forEach((child) => {
          const matched = new RegExp(query, 'ig').test(child.title);

          if (matched) {
            this.highlighted = child;

            child.originalTitle = child.title;
            child.title = highlight(query, highlightColorClass, child.title);

            if (!filtered.find((f) => f.title === module.title)) {
              filtered.push(module);
            }

            childMatched = true;
          }
        });

        // Search the category title if no child matches the query
        if (!childMatched) {
          const matched = new RegExp(query, 'ig').test(module.title);
          if (matched) {
            module.originalTitle = module.title;
            module.title = highlight(query, highlightColorClass, module.title);

            if (!filtered.find((f) => f.title === module.title)) {
              filtered.push(module);
            }
          }
        }
      });

      return filtered;
    },
  },
  watch: {
    filtered: {
      handler(v) {
        this.$emit('search-complete', v.length);
      },
    },
  },
  created() {
    this.lastActivatedModuleKey = null;
  },
  mounted() {
    // Bind forward slash to focus the search input.
    window.onkeyup = (e) => {
      if (
        e.key === '/'
        && e.target.nodeName !== 'INPUT'
        && e.target.nodeName !== 'TEXTAREA'
      ) {
        this.focus();
      }
    };

    this.categories = this.getMappedScaffoldingCategories();

    this.mounted = true;

    this.focus();
  },
  methods: {
    focus() {
      this.$nextTick(() => {
        if (this.$refs.searchInput) {
          this.$refs.searchInput.focus();
        }
      });
    },

    focusLost() {
      this.pHolder = `${this.placeholder} (Press / to focus)`;
    },

    gainedFocus() {
      this.pHolder = this.placeholder;
    },

    clear() {
      this.searchTerm = '';
    },

    getMappedScaffoldingCategories() {
      return this.sort(categories).map((c) => {
        const m = c;

        m.originalTitle = m.title;

        if (m.sort === false) {
          return m;
        }

        if (m.children) {
          m.children = this.sort(m.children);
          m.children.forEach((child) => child.originalTitle = child.title);
        }

        return m;
      });
    },

    sort(obj) {
      return obj.sort((a, b) => {
        const x = a.title.toLowerCase();
        const y = b.title.toLowerCase();
        // eslint-disable-next-line no-nested-ternary
        return x < y ? -1 : x > y ? 1 : 0;
      });
    },

    showModule(module) {
      this.$emit('selected', module);
      this.activeModule = module.key;
      this.searchTerm = '';
      this.lastActivatedModuleKey = module.key;
      this.$emit('closed');
    },

    setLastActivatedModuleKey(key) {
      this.lastActivatedModuleKey = key;
    },

    cancelSearch() {
      this.searchTerm = '';
      this.$emit('cleared');
    },

    showHighlightedModule() {
      if (this.highlighted) {
        this.showModule(this.highlighted);
      }
    },

    getTooltipText(tooltip) {
      let tooltipText = tooltip.text;
      if (tooltip.links) {
        tooltipText += '<br/>';

        tooltip.links.forEach((link) => {
          let title = link;
          if (link.title) title = link.title;
          tooltipText += `<br/><a href="${link.href}" target="_blank" class="text-complete m-t-5"><i class="fa fa-external-link"></i> ${title}</a>`;
        });
      }
      tooltipText += '<p></p>';
      return tooltipText;
    },
  },
};
</script>

<style scoped>
.dot-separator {
    opacity: 0.4;
}

.module-link:hover {
    color: red !important;
}
</style>
