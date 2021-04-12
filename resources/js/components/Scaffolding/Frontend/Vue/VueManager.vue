<template>
  <scaffolding-component-container heading="Manage Vue Setup" :loading="loading || fetchingMutations">
    <row>
     <column centered>
       <pg-check-box v-model="config.install" no-margin centered label="Install and Configure Vue" />
     </column>

      <column v-if="config.install">
        <row>
          <column>
            <basic-content-section heading="Configure Vue" prepend-separator>
              <row>
                <column centered>
                  <pg-check-box v-model="config.addRouter" no-margin centered label="Install Router" />
                  <pg-check-box v-model="config.addStore" no-margin centered label="Install Store" />
                </column>
              </row>
            </basic-content-section>

            <basic-content-section heading="Configure main.js" prepend-separator>
              <row>
                <column centered>
                  <pg-check-box v-model="config.createMain" no-margin centered label="Create main.js" />
                </column>
              </row>
            </basic-content-section>

            <basic-content-section heading="Configure UI Library" prepend-separator>
              <row>
                <column centered>
                  <pg-check-box v-model="config.ui.element.install" no-margin centered label="Use Element UI" @change="onElementUILibToggled" />
                  <pg-check-box v-model="config.ui.vuetify.install" no-margin centered label="Use Vuetify" @change="onVuetifyLibToggled" />
                </column>
              </row>
            </basic-content-section>

            <basic-content-section heading="Configure Element UI" prepend-separator v-if="config.ui.element.install">
              <row>
                <column centered>
                  <pg-check-box :value="config.ui.element.importType === 'full'"
                                no-margin
                                centered
                                label="Full Import"
                                :ensure-state="config.ui.element.importType !== 'onDemand'"
                                @change="onElementUIFullInstallToggled" />
                  <pg-check-box :value="config.ui.element.importType === 'onDemand'"
                                no-margin
                                centered
                                label="On Demand Import"
                                :ensure-state="config.ui.element.importType !== 'full'"
                                @change="onElementUIOnDemandInstallToggled" />
                </column>
              </row>
            </basic-content-section>

            <basic-content-section heading="Pick Element UI On Demand Components"
                                   prepend-separator
                                   v-if="config.ui.element.install && config.ui.element.importType === 'onDemand'">
              <row>
                <column size="4" offset="4">
                  <simple-select filterable
                                 multiple
                                 collapse-tags
                                 full-width
                                 v-model="config.ui.element.onDemandComponents"
                                 :entities="elementComponentTypes">
                  <template slot-scope="{ entity }">
                      <el-option :key="entity.value"
                                 :value="entity.value"
                                 :label="entity.label" />
                    </template>
                  </simple-select>
                </column>
              </row>
            </basic-content-section>

            <basic-content-section :heading="`Components (${vueComponents.length})`" prepend-separator>
              <row>
                <column size="4" offset="4" :key="c.id" v-for="c in vueComponents">
                  <form-input-group compact>
                    <toggle-button :value="c.render"
                                   off-color-class="info"
                                   on-color-class="green"
                                   text="Render" @change="onRenderToggled($event, c)" />
                    <pg-input v-model="c.name"
                              :ref="`${c.id}`"
                              placeholder="Component name..."
                              @keyup.native.enter="onAddComponent" />
                    <simple-button color-class="danger" @click="onDeleteComponent(c)">
                      <i class="fa fa-close"></i>
                    </simple-button>
                  </form-input-group>
                </column>
                <column :push5="vueComponents.length > 0" size="4" offset="4" :centered="!vueComponents.length">
                  <simple-button color-class="primary" @click="onAddComponent">
                    <i class="fa fa-plus"></i>
                    <span v-if="!vueComponents.length">Add Component</span>
                  </simple-button>
                </column>
              </row>
            </basic-content-section>
          </column>
        </row>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import BasicContentSection from '@/components/Content/BasicContentSection';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import PgInput from '@/components/Forms/PgInput';
import ToggleButton from '@/components/Forms/Buttons/ToggleButton';
import elementComponentTypes from '@/data/vue/element/elementComponentTypes';
import SimpleSelect from '@/components/Select/SimpleSelect';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';

export default {
  name: 'VueManager',
  mixins: [asyncImports, mutations],
  components: {
    SimpleButton,
    SimpleSelect,
    ToggleButton,
    PgInput,
    FormInputGroup,
    BasicContentSection,
    PgCheckBox,
    Row,
    Column,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      config: {
        install: false,
        addRouter: true,
        addStore: true,
        createMain: true,
        ui: {
          element: {
            install: false,
            importType: 'full',
            onDemandComponents: ['Button', 'Select'],
          },
          vuetify: {
            install: false,
          },
        },
      },
      vueComponents: [],
      elementComponentTypes: [],
    };
  },
  computed: {
    persistable() {
      return {
        config: this.config,
        components: this.vueComponents.filter((v) => v.name.trim() !== ''),
      };
    },
  },
  watch: {
    persistable: {
      handler(v) {
        const payload = {
          name: 'Vue Setup',
          path: 'frontend/vue',
          value: v,
        };

        this.mutate(payload);
      },
      deep: true,
    },
  },
  async created() {
    this.elementComponentTypes = elementComponentTypes.map((c) => ({
      value: c,
      label: c,
    }));

    this.loading = true;
    await this.sync();
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'frontend/vue' });
      if (data.value) {
        this.config = data.value.config || this.config;
        this.vueComponents = data.value.components || this.vueComponents;
      }
    },

    onAddComponent() {
      const component = {
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        name: '',
        render: !this.vueComponents.length,
      };

      this.vueComponents.push(component);

      this.$nextTick(() => {
        if (this.$refs[component.id]) {
          this.$refs[component.id][0].focus();
        }
      });
    },

    onDeleteComponent(component) {
      const cId = this.vueComponents.findIndex((c) => c.id === component.id);
      if (cId > -1) {
        this.vueComponents.splice(cId, 1);
      }
    },

    onRenderToggled(active, component) {
      if (active) {
        component.render = true;
        this.vueComponents.filter((c) => c.id !== component.id).forEach((c) => c.render = false);
      }
    },

    onElementUILibToggled(active) {
      if (active) {
        this.config.ui.vuetify.install = false;
      }
    },

    onVuetifyLibToggled(active) {
      if (active) {
        this.config.ui.element.install = false;
      }
    },

    onElementUIOnDemandInstallToggled(active) {
      if (active) {
        this.config.ui.element.importType = 'onDemand';
      } else {
        this.config.ui.element.importType = null;
      }
    },

    onElementUIFullInstallToggled(active) {
      if (active) {
        this.config.ui.element.importType = 'full';
      } else {
        this.config.ui.element.importType = null;
      }
    },
  },
};
</script>

<style scoped>

</style>
