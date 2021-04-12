<template>
    <scaffolding-component-container heading="Configure Layout" :loading="loading || fetchingMutations">
        <row>
            <column size="8" offset="2">
                <row>
                    <column size="6" offset="3">
                        <pg-labeled-input v-model="layout.name"
                                          ref="layoutNameInput"
                                          label="Layout Name"
                                          @input="handleLayoutNameChanged($event, layout)"/>
                    </column>
                </row>
            </column>

            <column>
                <separator/>
            </column>
        </row>
        <row>
            <column>
                <tabs-manager :tabs="steps" :path="`ui/custom/tabs/assets/definition/active/${layout.id}`">
                    <template :slot="step.id" v-for="step in steps">
                        <component :is="step.component"
                                   :layout="layout"
                                   :layout-id="layout.id"
                                   :layout-name="layout.name"
                                   :layout-path="layout.path"
                                   :key="step.id"
                                   @init-start="handleComponentInitStart"
                                   @init-end="handleComponentInitEnd" />
                    </template>
                </tabs-manager>
            </column>
        </row>
    </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import ContentCard from '@/components/Cards/ContentCard';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import mutations from '@/mixins/mutations';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import Separator from '@/components/Layout/Separator';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import TabsManager from '@/components/Tabs/TabsManager';
import AssetsCompilation from '@/components/Scaffolding/UserInterface/Custom/Blade/Components/AssetsCompilation';
import LayoutDefinition from '@/components/Scaffolding/UserInterface/Custom/Blade/Components/Definition/LayoutDefinition';
import BladeHelpers from '@/helpers/blade_helpers';

export default {
  name: 'BladeLayoutBuilder',
  props: {
    persisted: {},
  },
  mixins: [mutations],
  components: {
    TabsManager,
    PgCheckBox,
    Separator,
    PgLabeledInput,
    ScaffoldingComponentContainer,
    IndeterminateProgressBar,
    Column,
    ContentCard,
    Row,
  },
  data() {
    const layout = this.persisted || {
      id: `L${Math.round(Math.random() * Number.MAX_SAFE_INTEGER)}`,
      name: 'unnamed_layout',
      path: 'layouts/unnamed_layout.blade.php',
    };

    layout.strict = layout.strict || true;
    layout.partials = layout.partials || [];

    return {
      loading: false,
      componentLoading: false,
      step: null,
      steps: [
        {
          id: 'LayoutDefinition',
          label: 'Definition',
          component: LayoutDefinition,
        },
        {
          id: 'AssetsCompilation',
          label: 'Compilation',
          component: AssetsCompilation,
        },
      ],
      layout,
    };
  },
  computed: {
    activeStep() {
      return this.step || this.steps[0];
    },

    hasNextStep() {
      return !this.activeStep ? true : this.steps.find((s) => s.id === this.activeStep.id + 1);
    },

    nextStep() {
      return !this.activeStep ? this.steps[0] : this.steps.find((s) => s.id === this.activeStep.id + 1);
    },

    hasPrevStep() {
      return this.steps.find((s) => s.id === this.activeStep.id - 1);
    },

    prevStep() {
      return !this.activeStep ? this.steps[0] : this.steps.find((s) => s.id === this.activeStep.id - 1);
    },
  },
  async created() {
    this.loading = true;
    this.loading = false;

    this.focusDefaultInput();
  },
  methods: {
    handleLayoutNameChanged(newLayoutName, layout) {
      this.syncPath(newLayoutName, layout);
      this.$emit('updated', layout);
    },

    handleLayoutPathChanged(newLayoutPath, layout) {
      this.$emit('updated', layout);
    },

    syncPath(update, layout) {
      const dir = this.fs.dir(layout.path);
      const fn = this.fs.fn(update);
      layout.path = `${dir}/${BladeHelpers.bladeTemplateFilename(fn)}`;
    },

    beforeUpload() {
      return false;
    },

    incrementStep() {
      const { activeStep } = this;
      this.step = this.steps.find((s) => s.id === activeStep.id + 1);
    },

    decrementStep() {
      const { activeStep } = this;
      this.step = this.steps.find((s) => s.id === activeStep.id - 1);
    },

    handleComponentInitStart() {
      this.componentLoading = true;
    },

    handleComponentInitEnd() {
      this.componentLoading = false;
    },

    focusDefaultInput() {
      this.$nextTick(() => {
        if (this.$refs.layoutNameInput) {
          this.$refs.layoutNameInput.focus();
        }
      });
    },
  },
};
</script>

<style scoped>

</style>
