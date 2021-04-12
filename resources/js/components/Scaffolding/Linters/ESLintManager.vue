<template>
  <scaffolding-component-container heading="Configure ESLint" :loading="loading || fetchingMutations">
    <row>
      <column centered>
        <pg-check-box v-model="config.create" no-margin centered label="Create ESLint Configuration"/>
      </column>

      <column v-if="config.create">
        <basic-content-section heading="Environment" prepend-separator>
          <row>
            <column centered>
              <pg-check-box v-model="config.env.browser" no-margin centered label="Browser"/>
              <pg-check-box v-model="config.env.es6" no-margin centered label="ES6"/>
              <pg-check-box v-model="config.env.node" no-margin centered label="Node"/>
            </column>
          </row>
        </basic-content-section>
        <basic-content-section heading="Parser" prepend-separator>
          <row>
            <column centered>
                <pg-check-box disabled v-model="config.parser.babelEsLint" no-margin centered label="babel-eslint"/>
            </column>
          </row>
        </basic-content-section>
        <basic-content-section heading="Rules" prepend-separator>
          <row>
            <column centered>
                <pg-check-box v-model="config.extends.airbnbBase" no-margin centered label="airbnb-base"/>
                <pg-check-box color-class="green"
                              v-model="config.extends.vueEssential"
                              no-margin
                              centered
                              label="vue/essential" @change="onVueEssentialStateChanged" />
                <pg-check-box color-class="green"
                              v-model="config.extends.vueRecommended"
                              no-margin
                              centered
                              label="vue/recommended" @change="onVueRecommendedStateChanged" />
                <pg-check-box color-class="green"
                              v-model="config.extends.vueStronglyRecommended"
                              no-margin
                              centered
                              label="vue/strongly-recommended" @change="onVueStronglyRecommendedStateChanged" />
            </column>
          </row>
        </basic-content-section>
        <basic-content-section heading="Module Resolution" prepend-separator>
          <row>
            <column centered>
                <pg-check-box v-model="config.resolution.mapJsDir" no-margin centered label="Map @ to resources/js"/>
            </column>
          </row>
        </basic-content-section>
        <basic-content-section heading="Overrides" prepend-separator>
          <row>
            <column size="2" offset="5">
              <form-input-title title="Max Line Length" />
              <pg-input class="text-center" v-model="config.overrides.maxLineLength" />
            </column>
            <column push10 centered>
                <pg-check-box v-model="config.overrides.noReturnAssignment" no-margin centered label="No Return Assignments"/>
                <pg-check-box v-model="config.overrides.noParamReassignments" no-margin centered label="No Parameter Reassignments"/>
            </column>
          </row>
        </basic-content-section>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import BasicContentSection from '@/components/Content/BasicContentSection';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import PgInput from '@/components/Forms/PgInput';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';

export default {
  name: 'ESLintManager',
  mixins: [asyncImports, mutations],
  components: {
    PgInput,
    FormInputTitle,
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
        create: false,
        env: {
          browser: true,
          es6: true,
          node: true,
        },
        parser: {
          babelEsLint: true,
        },
        extends: {
          airbnbBase: true,
          vueEssential: true,
          vueRecommended: false,
          vueStronglyRecommended: false,
        },
        resolution: {
          mapJsDir: true,
        },
        overrides: {
          maxLineLength: 120,
          noReturnAssignment: true,
          noParamReassignments: true,
        },
      },
    };
  },
  watch: {
    config: {
      handler(v) {
        const payload = {
          name: 'ESLint Config',
          path: 'linters/eslint',
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
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'linters/eslint' });
      this.config = data.value || this.config;
    },
    onVueEssentialStateChanged(active) {
      if (active) {
        this.config.extends.vueRecommended = false;
        this.config.extends.vueStronglyRecommended = false;
      }
    },
    onVueRecommendedStateChanged(active) {
      if (active) {
        this.config.extends.vueEssential = false;
        this.config.extends.vueStronglyRecommended = false;
      }
    },
    onVueStronglyRecommendedStateChanged(active) {
      if (active) {
        this.config.extends.vueEssential = false;
        this.config.extends.vueRecommended = false;
      }
    },
  },
};
</script>

<style scoped>

</style>
