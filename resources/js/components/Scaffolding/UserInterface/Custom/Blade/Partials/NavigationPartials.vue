<template>
  <div>
    <row v-if="loading">
      <column size="4" offset="4">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column centered>
        <text-block info hinted>
          Quickly create (and optionally replace) the navigation menu.
        </text-block>
        <separator />
        <text-block info>
          The navigation partial will be placed in <span class="text-info bold">views/partials</span> directory.
        </text-block>
        <separator />
      </column>
      <column centered>
        <basic-content-section heading="List Configuration">
          <row>
            <column>
              <basic-content-section heading="Replacement" heading-color="info">
                <row>
                  <column>
                    <el-radio :disabled="project && project.downloaded" v-model="config.navContainerType" label="first">
                      Replace the first navigation container (nav tag)
                    </el-radio>

                    <el-radio :disabled="project && project.downloaded" v-model="config.navContainerType" label="custom">
                      Use custom selector to find the navigation container
                    </el-radio>
                  </column>
                  <column size="6" offset="3" push10 v-if="config.navContainerType === 'custom'">
                    <pg-input placeholder="Custom navigation selector (e.g. .menu_container > nav)" v-model="config.navContainer" />
                  </column>
                  <column>
                    <text-block danger hinted class="p-t-10" v-if="!validatedItems.length && config.cleanup">
                      Only applies if you have valid navigation links defined below.
                    </text-block>
                  </column>
                </row>
              </basic-content-section>
            </column>

            <column>
              <basic-content-section heading="Nav Tag Classes" heading-color="info" prepend-separator>
                <row>
                  <column size="6" offset="3">
                    <pg-input v-model="config.navClass" placeholder="e.g. main-menu" />
                  </column>
                </row>
              </basic-content-section>
            </column>

            <column>
              <basic-content-section heading="List Style" heading-color="info" prepend-separator>
                <el-radio :disabled="project && project.downloaded" v-model="config.listStyle" label="ul">
                  Unordered (ul)
                </el-radio>
                <el-radio :disabled="project && project.downloaded" v-model="config.listStyle" label="ol">
                  Ordered (ol)
                </el-radio>
              </basic-content-section>
            </column>

            <column>
              <basic-content-section :heading="`List Classes (${config.listStyle})`" heading-color="info" prepend-separator>
                <row>
                  <column size="6" offset="3">
                    <pg-input v-model="config.listClass" placeholder="e.g. navbar-nav" />
                  </column>
                </row>
              </basic-content-section>
            </column>

            <column>
              <basic-content-section heading="List Item Classes (li)" heading-color="info" prepend-separator>
                <row>
                  <column size="6" offset="3">
                    <form-input-title title="Regular" />
                    <pg-input v-model="config.listItemClass" placeholder="e.g. nav-item" />
                  </column>
                  <column push10 size="6" offset="3">
                    <form-input-title title="Selected" />
                    <pg-input v-model="config.listItemSelectedClass" placeholder="e.g. active" />
                  </column>
                </row>
              </basic-content-section>
            </column>

            <column>
              <basic-content-section heading="Anchor Classes (a)" heading-color="info" prepend-separator>
                <row>
                  <column size="6" offset="3">
                    <pg-input v-model="config.listAnchorClass" placeholder="e.g. nav-link" />
                  </column>
                </row>
              </basic-content-section>
            </column>
          </row>
        </basic-content-section>
      </column>

      <column>
        <basic-content-section heading="Navigation Links" prepend-separator>
          <draggable v-model="items" group>
            <row :key="item.id" v-for="item in items">
              <column size="6" offset="3">
                <form-input-group compact>
                  <pg-input v-model="item.text" placeholder="Anchor text (e.g. Home)..." style="width: 35%;" />
                  <simple-select placeholder="View route..." style="width: 50%;"
                                 filterable
                                 clearable
                                 v-model="item.uri"
                                 :entities="routableViews">
                    <template slot-scope="{ entity }">
                      <el-option :key="entity.id"
                                 :value="entity.id"
                                 :label="`${entity.controllerName}@${entity.methodName}`">
                        <template>
                          <span>{{ entity.uri }}</span>
                          <span class="pull-right m-l-20">{{entity.controllerName}}@{{entity.methodName}}</span>
                        </template>
                      </el-option>
                    </template>
                  </simple-select>
                  <simple-button color-class="danger" @click="onRemoveItem(item)">
                    <i class="fa fa-close" />
                  </simple-button>
                </form-input-group>
              </column>
            </row>
          </draggable>
          <row>
            <column push5 size="6" offset="3">
              <simple-button color-class="primary" @click="addListItem">
                <i class="fa fa-plus"></i>
              </simple-button>
            </column>
          </row>
        </basic-content-section>
      </column>
    </row>
  </div>
</template>

<script>
import Draggable from 'vuedraggable';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import BasicContentSection from '@/components/Content/BasicContentSection';
import PgInput from '@/components/Forms/PgInput';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import TextBlock from '@/components/Typography/Decorated/TextBlock';
import Separator from '@/components/Layout/Separator';
import { mapState } from 'vuex';
import SimpleSelect from '@/components/Select/SimpleSelect';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';
import FormInputTitle from '@/components/Typography/FormInputTitle';

export default {
  name: 'NavigationPartials',
  mixins: [mutations],
  props: {
    views: Array,
    controllers: Array,
  },
  components: {
    FormInputTitle,
    Draggable,
    SimpleButton,
    SimpleSelect,
    Separator,
    TextBlock,
    IndeterminateProgressBar,
    FormInputGroup,
    PgInput,
    BasicContentSection,
    Row,
    Column,
  },
  data() {
    return {
      loading: false,

      config: {
        navContainerType: 'first',
        navContainer: '',
        listStyle: 'ul',
        navClass: '',
        listClass: 'navbar-nav',
        listItemClass: 'nav-item',
        listItemSelectedClass: 'active',
        listAnchorClass: 'nav-link',
      },

      items: [],
    };
  },
  computed: {
    ...mapState('project', ['project']),

    routableViews() {
      return (this.views || [])
        .filter((v) => v.controller)
        .map((v) => (
          {
            ...v,
            methodName: `show${this.str.studly(v.name)}`,
            controllerName: (this.controllers.find((c) => c.id === v.controller) || {}).name || 'Unnamed Controller',
          }
        ));
    },

    validatedItems() {
      return this.items.filter((i) => i.text && i.text.trim() !== '');
    },

    persistable() {
      return {
        config: this.config,
        items: this.items,
      };
    },
  },
  watch: {
    persistable: {
      handler(v) {
        const payload = {
          name: 'Navigation Partials',
          path: 'ui/custom/partials/navigation',
          value: v,
        };

        this.mutate(payload);
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;
    await this.sync();

    if (!this.items.length) {
      this.addListItem();
    }
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'ui/custom/partials/navigation' });
      const value = this.getPersistedMutationValue(data);

      if (value) {
        this.config = value.config || this.config;
        this.items = value.items || this.items;
      }
    },
    addListItem() {
      this.items.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        text: '',
        uri: '',
      });
    },
    onRemoveItem(item) {
      const itemIndex = this.items.findIndex((i) => i.id === item.id);

      if (itemIndex > -1) {
        this.items.splice(itemIndex, 1);
      }
    },
  },
};
</script>

<style scoped>

</style>
