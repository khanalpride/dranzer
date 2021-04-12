<template>
    <scaffolding-component-container heading="Configure Form Validation" :loading="loading || fetchingMutations">
        <row>
            <column centered v-if="!models.length">
                <p class="text-primary no-margin"><i class="fa fa-info"></i> There are no models available to create rules.</p>
            </column>
            <column v-else>
              <tabs-manager :tabs="models" path="config/validation/tabs/active">
                <template :slot="model.id" v-for="model in models">
                  <row :key="model.id">
                    <column>
                      <basic-content-section heading="Default Request Authorization">
                        <form-validation-model-config :model-id="model.id" />
                      </basic-content-section>
                    </column>
                    <column>
                      <basic-content-section heading="Columns" prepend-separator>
                        <tabs-manager :key="model.id"
                                      :tabs="model.columns"
                                      :path="`config/validation/tabs/${model.id}/active`">
                          <template :slot="column.id" v-for="column in model.columns">
                            <form-validation-mapping :key="column.id"
                                                     :column="column"
                                                     :auth="model.auth"
                                                     :model="model"/>
                          </template>
                        </tabs-manager>
                      </basic-content-section>
                    </column>
                  </row>
                </template>
              </tabs-manager>
            </column>
        </row>
    </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import sharedMutations from '@/mixins/shared_mutations';
import FormValidationMapping from '@/components/Scaffolding/Validation/FormValidationMapping';
import TabsManager from '@/components/Tabs/TabsManager';
import BasicContentSection from '@/components/Content/BasicContentSection';
import FormValidationModelConfig from '@/components/Scaffolding/Validation/FormValidationModelConfig';

export default {
  name: 'FormRequestValidationContainer',
  mixins: [asyncImports, mutations, sharedMutations],
  components: {
    FormValidationModelConfig,
    BasicContentSection,
    TabsManager,
    FormValidationMapping,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  data() {
    return {
      loading: false,

      blueprints: [],

      models: [],

      activeTab: null,

      rules: [
        {
          name: 'active',
          active: false,
        },
        {
          name: 'active_url',
          active: false,
        },
        {
          name: 'after',
          active: false,
          input1: '',
          input1Desc: '<span class="text-info bold">date</span> value for <span class="text-green bold">after</span>',
        },
        {
          name: 'after_or_equal',
          active: false,
          input1: '',
          input1Desc: '<span class="text-info bold">date</span> value for <span class="text-green bold">after_or_equal</span>',
        },
        {
          name: 'alpha',
          active: false,
        },
        {
          name: 'alpha_dash',
          active: false,
        },
        {
          name: 'array',
          active: false,
        },
        {
          name: 'bail',
          active: false,
        },
        {
          name: 'before',
          input1: '',
          input1Desc: '<span class="text-info bold">date</span> value for <span class="text-green bold">before</span>',
          active: false,
        },
        {
          name: 'before_or_equal',
          input1: '',
          input1Desc: '<span class="text-info bold">date</span> value for <span class="text-green bold">before_or_equal</span>',
          active: false,
        },
        {
          name: 'between',
          active: false,
          input1: '',
          input2: '',
          input1Desc: '<span class="text-info bold">min</span> value for <span class="text-green bold">between</span>',
          input2Desc: '<span class="text-info bold">max</span> value for <span class="text-green bold">between</span>',
        },
        {
          name: 'boolean',
          active: false,
        },
        {
          name: 'confirmed',
          active: false,
        },
        {
          name: 'date',
          active: false,
        },
        {
          name: 'date_equals',
          input1: '',
          input1Desc: '<span class="text-info bold">date</span> value for <span class="text-green bold">date_equals</span>',
          active: false,
        },
        {
          name: 'date_format',
          input1: '',
          input1Desc: '<span class="text-info bold">format</span> for <span class="text-green bold">date_format</span>',
          active: false,
        },
        {
          name: 'different',
          input1: '',
          input1Desc: '<span class="text-info bold">field</span> for <span class="text-green bold">different</span>',
          active: false,
        },
        {
          name: 'digits',
          input1: '',
          input1Desc: '<span class="text-info bold">length</span> for <span class="text-green bold">digits</span>',
          active: false,
        },
        {
          name: 'digits_between',
          active: false,
          input1: '',
          input2: '',
          input1Desc: '<span class="text-info bold">min</span> value for <span class="text-green bold">digits_between</span>',
          input2Desc: '<span class="text-info bold">max</span> value for <span class="text-green bold">digits_between</span>',
        },
        {
          name: 'distinct',
          active: false,
        },
        {
          name: 'email',
          active: false,
        },
        {
          name: 'starts_with',
          input1: '',
          input1Desc: '<span class="text-info bold">starting substring</span> for <span class="text-green bold">starts_with</span>',
          active: false,
        },
        {
          name: 'ends_with',
          input1: '',
          input1Desc: '<span class="text-info bold">ending substring</span> for <span class="text-green bold">ends_with</span>',
          active: false,
        },
        {
          name: 'exclude_if',
          active: false,
          input1: '',
          input2: '',
          input1Desc: '<span class="text-info bold">anotherfield</span> value for <span class="text-green bold">exclude_if</span>',
          input2Desc: '<span class="text-info bold">value</span> for <span class="text-green bold">exclude_if</span>',
        },
        {
          name: 'exclude_unless',
          active: false,
          input1: '',
          input2: '',
          input1Desc: '<span class="text-info bold">anotherfield</span> value for <span class="text-green bold">exclude_unless</span>',
          input2Desc: '<span class="text-info bold">value</span> for <span class="text-green bold">exclude_unless</span>',
        },
        {
          name: 'unique',
          input1: '',
          input1Desc: '<span class="text-info bold">table</span> for <span class="text-green bold">unique</span>',
          active: false,
        },
        {
          name: 'exists',
          input1: '',
          input1Desc: '<span class="text-info bold">table</span> for <span class="text-green bold">exists</span>',
          active: false,
        },
        {
          name: 'file',
          active: false,
        },
        {
          name: 'filled',
          active: false,
        },
        {
          name: 'gt',
          input1: '',
          input1Desc: '<span class="text-info bold">field</span> for <span class="text-green bold">gt</span>',
          active: false,
        },
        {
          name: 'gte',
          input1: '',
          input1Desc: '<span class="text-info bold">field</span> for <span class="text-green bold">gte</span>',
          active: false,
        },
        {
          name: 'lt',
          input1: '',
          input1Desc: '<span class="text-info bold">field</span> for <span class="text-green bold">lt</span>',
          active: false,
        },
        {
          name: 'lte',
          input1: '',
          input1Desc: '<span class="text-info bold">field</span> for <span class="text-green bold">lte</span>',
          active: false,
        },
        {
          name: 'image',
          active: false,
        },
        {
          name: 'in_array',
          input1: '',
          input1Desc: '<span class="text-info bold">anotherfield</span> value for <span class="text-green bold">in_array</span>',
          active: false,
        },
        {
          name: 'integer',
          active: false,
        },
        {
          name: 'ip',
          active: false,
        },
        {
          name: 'ipv4',
          active: false,
        },
        {
          name: 'ipv6',
          active: false,
        },
        {
          name: 'json',
          active: false,
        },
        {
          name: 'min',
          input1: '',
          input1Desc: '<span class="text-info bold">value</span> for <span class="text-green bold">min</span>',
          active: false,
        },
        {
          name: 'max',
          input1: '',
          input1Desc: '<span class="text-info bold">value</span> for <span class="text-green bold">max</span>',
          active: false,
        },
        {
          name: 'mimetypes',
          input1: '',
          input1Desc: '<span class="text-info bold">types (comma sep.)</span> for <span class="text-green bold">mimetypes</span>',
          active: false,
        },
        {
          name: 'mimes',
          input1: '',
          input1Desc: '<span class="text-info bold">types (comma sep.)</span> for <span class="text-green bold">mimes</span>',
          active: false,
        },
        {
          name: 'regex',
          input1: '',
          input1Desc: '<span class="text-info bold">pattern</span> for <span class="text-green bold">regex</span>',
          active: false,
        },
        {
          name: 'not_regex',
          input1: '',
          input1Desc: '<span class="text-info bold">pattern</span> for <span class="text-green bold">not_regex</span>',
          active: false,
        },
        {
          name: 'nullable',
          active: false,
        },
        {
          name: 'numeric',
          active: false,
        },
        {
          name: 'password',
          active: false,
        },
        {
          name: 'present',
          active: false,
        },
        {
          name: 'required',
          active: false,
        },
        {
          name: 'required_if',
          active: false,
          input1: '',
          input2: '',
          input1Desc: '<span class="text-info bold">anotherfield</span> value for <span class="text-green bold">required_if</span>',
          input2Desc: '<span class="text-info bold">value</span> for <span class="text-green bold">required_if</span>',
        },
        {
          name: 'required_unless',
          active: false,
          input1: '',
          input2: '',
          input1Desc: '<span class="text-info bold">anotherfield</span> value for <span class="text-green bold">required_unless</span>',
          input2Desc: '<span class="text-info bold">value</span> for <span class="text-green bold">required_unless</span>',
        },
        {
          name: 'required_with',
          input1: '',
          input1Desc: '<span class="text-info bold">fields (comma sep.)</span> for <span class="text-green bold">required_with</span>',
          active: false,
        },
        {
          name: 'required_with_all',
          input1: '',
          input1Desc: '<span class="text-info bold">fields (comma sep.)</span> for <span class="text-green bold">required_with_all</span>',
          active: false,
        },
        {
          name: 'required_without',
          input1: '',
          input1Desc: '<span class="text-info bold">fields (comma sep.)</span> for <span class="text-green bold">required_without</span>',
          active: false,
        },
        {
          name: 'required_without_all',
          input1: '',
          input1Desc: '<span class="text-info bold">fields (comma sep.)</span> for <span class="text-green bold">required_without_all</span>',
          active: false,
        },
        {
          name: 'same',
          input1: '',
          input1Desc: '<span class="text-info bold">field</span> for <span class="text-green bold">same</span>',
          active: false,
        },
        {
          name: 'string',
          active: false,
        },
        {
          name: 'timezone',
          active: false,
        },
        {
          name: 'url',
          active: false,
        },
        {
          name: 'uuid',
          active: false,
        },
      ],

      modelTabs: [
        {
          id: 'create',
          label: 'Create',
        },
        {
          id: 'update',
          label: 'Update',
        },
      ],
    };
  },
  async created() {
    this.loading = true;
    await this.assignBlueprints();

    const models = this.blueprints.filter((s) => s.modelName).map((m) => ({
      id: m.id,
      name: m.modelName,
      tableName: m.tableName,
      modelName: m.modelName,
      columns: m.columns,
    }));

    models.forEach((m) => {
      m.columns = m.columns.map((c) => ({
        id: `T${c.id}`,
        name: c.name,
      }));
      m.tab = m.columns.length ? m.columns[0].name : null;
      m.columns.forEach((c) => {
        const rules = JSON.parse(JSON.stringify(this.rules));

        const uniqueRule = rules.find((r) => r.name === 'unique');

        if (uniqueRule) {
          uniqueRule.input1 = m.tableName;
        }

        c.rules = rules;
        c.applied = [];
      });
    });

    this.models = models;

    this.$nextTick(() => {
      if (this.models.length) {
        this.activeTab = this.models[0].modelName;
        this.models[0].tab = this.models[0].columns[0].name;
      }
    });

    await this.syncActiveTabs();

    this.loading = false;
  },
  methods: {
    async syncActiveTabs() {
      const { data } = await this.mutation({ path: 'validation/tabs/active' });
      this.activeTab = data.value || this.activeTab;
      await this.syncModelTab(this.activeTab);
    },

    async syncModelTab(modelName) {
      if (!modelName) {
        return;
      }

      const { data } = await this.mutation({ path: `validation/tabs/${modelName}/active` });
      const model = this.models.find((m) => m.modelName === modelName);

      if (model) {
        model.tab = data.value || (model.columns.length ? model.columns[0].name : null);
      }
    },

    persistActiveTab() {
      const name = 'Active Form Request Validation Tab';
      const path = 'validation/tabs/active';
      const value = this.activeTab;

      this.mutate(value, name, path);
    },

    modelTabChanged(e, model) {
      this.$nextTick(() => {
        this.$forceUpdate();
        this.persistModelActiveTab(e.paneName, model);
      });
    },

    persistModelActiveTab(tabName, model) {
      const name = 'Active Form Request Model Tab';
      const path = `validation/tabs/${model.id}/active`;

      const payload = {
        name,
        path,
        value: tabName,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
