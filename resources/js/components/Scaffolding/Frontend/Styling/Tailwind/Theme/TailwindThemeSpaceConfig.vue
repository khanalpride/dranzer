<template>
  <div>
    <row v-if="loading">
      <column size="4" offset="4">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column>
        <blockquote class="hint-text">
          Use the spacing key in the theme section of your tailwind.config.js file to customize Tailwind's
          <a href="https://tailwindcss.com/docs/customizing-spacing#default-spacing-scale"
             target="_blank">default spacing/sizing scale</a><span>.</span>
          By default the spacing scale is inherited by the
          padding, margin, width, height, maxHeight, gap, inset, space, and translate core plugins.
        </blockquote>
        <separator />
      </column>
      <column>
        <basic-content-section heading="Default Spacing">
          <row>
            <column centered>
              <pg-check-box centered v-model="showDefaultSpacingRef" no-margin label="Show Default Spacing Reference" />
              <separator />
            </column>
            <column centered v-if="showDefaultSpacingRef">
              <tailwind-spacing-reference />
              <separator />
            </column>
            <column>
              <a href="#" class="small" @click.prevent="onCheckAllDefaultSpaces">Check All</a>
              <a href="#" class="m-l-5 small" @click.prevent="onToggleSelectedSpaces">Toggle Selection</a>
            </column>
            <column>
              <separator />
            </column>
          </row>
          <row>
            <column size="3" :key="space.name" v-for="space in defaultSpacing">
              <pg-check-box v-model="space.enabled">
              <span class="text-primary">
                <span class="text-complete bold">{{ space.name }}</span>
                <i class="fa fa-arrow-right text-info hint-text small m-l-5"></i>
                <span class="bold m-l-5">{{ getDefaultSpaceValue(space) }}</span>
              </span>
              </pg-check-box>
            </column>
          </row>
        </basic-content-section>
        <basic-content-section heading="Extend / Override Default Spacing Scale" prepend-separator>
          <row>
            <column>
              <pg-input v-model="customSpaceDefinition"
                        placeholder="Enter space name..."
                        @keydown.enter.native="onCreateCustomSpaceFromInput" />
            </column>
            <column push10 :key="space.id" v-for="space in customSpacing">
              <row>
                <column>
                  <pg-check-box no-margin :value="1" :label="space.name" @change="onCustomSpaceStateToggled($event, space)" />
                </column>
                <column push5 size="8" class="m-l-30">
                  <row>
                    <column size="3">
                      <form-input-title :centered="false" title="Mapped Value" />
                      <pg-input v-model="space.value" />
                    </column>
                  </row>
                </column>
              </row>
            </column>
          </row>
        </basic-content-section>
      </column>
    </row>
  </div>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import BasicContentSection from '@/components/Content/BasicContentSection';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Separator from '@/components/Layout/Separator';
import TailwindSpacingReference
  from '@/components/Scaffolding/Frontend/Styling/Tailwind/Theme/Components/TailwindSpacingReference';
import PgInput from '@/components/Forms/PgInput';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import { mapState } from 'vuex';

export default {
  name: 'TailwindThemeSpaceConfig',
  mixins: [mutations],
  components: {
    IndeterminateProgressBar,
    FormInputTitle,
    PgInput,
    TailwindSpacingReference,
    Separator,
    PgCheckBox,
    BasicContentSection,
    Row,
    Column,
  },
  data() {
    return {
      loading: false,

      ready: false,

      showDefaultSpacingRef: false,

      customSpaceDefinition: '',

      customSpacing: [],

      defaultSpacing: [
        { name: '0', value: '0px', enabled: true },
        { name: 'px', value: '1px', enabled: true },
        { name: '0.5', value: '0.125rem', enabled: true },
        { name: '1', value: '0.25rem', enabled: true },
        { name: '1.5', value: '0.375rem', enabled: true },
        { name: '2', value: '0.5rem', enabled: true },
        { name: '2.5', value: '0.625rem', enabled: true },
        { name: '3', value: '0.75rem', enabled: true },
        { name: '3.5', value: '0.875rem', enabled: true },
        { name: '4', value: '1rem', enabled: true },
        { name: '5', value: '1.25rem', enabled: true },
        { name: '6', value: '1.5rem', enabled: true },
        { name: '7', value: '1.75rem', enabled: true },
        { name: '8', value: '2rem', enabled: true },
        { name: '9', value: '2.25rem', enabled: true },
        { name: '10', value: '2.5rem', enabled: true },
        { name: '11', value: '2.75rem', enabled: true },
        { name: '12', value: '3rem', enabled: true },
        { name: '14', value: '3.5rem', enabled: true },
        { name: '16', value: '4rem', enabled: true },
        { name: '20', value: '5rem', enabled: true },
        { name: '24', value: '6rem', enabled: true },
        { name: '28', value: '7rem', enabled: true },
        { name: '32', value: '8rem', enabled: true },
        { name: '36', value: '9rem', enabled: true },
        { name: '40', value: '10rem', enabled: true },
        { name: '44', value: '11rem', enabled: true },
        { name: '48', value: '12rem', enabled: true },
        { name: '52', value: '13rem', enabled: true },
        { name: '56', value: '14rem', enabled: true },
        { name: '60', value: '15rem', enabled: true },
        { name: '64', value: '16rem', enabled: true },
        { name: '72', value: '18rem', enabled: true },
        { name: '80', value: '20rem', enabled: true },
        { name: '96', value: '24rem', enabled: true },
      ],
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  watch: {
    defaultSpacing: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({ name: 'Tailwind Default Spacing', path: 'tailwind/spacing/default', value: v });
      },
      deep: true,
    },
    customSpacing: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({ name: 'Tailwind Custom Spacing', path: 'tailwind/spacing/custom', value: v });
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;
    await this.syncDefaultSpacing();
    await this.syncCustomSpacing();
    this.loading = false;

    this.$nextTick(() => {
      this.ready = true;
    });
  },
  methods: {
    async syncDefaultSpacing() {
      const { data } = await this.mutation({ path: 'tailwind/spacing/default' });
      this.defaultSpacing = data.value || this.defaultSpacing;
    },
    async syncCustomSpacing() {
      const { data } = await this.mutation({ path: 'tailwind/spacing/custom' });
      this.customSpacing = data.value || this.customSpacing;
    },
    getDefaultSpaceValue(space) {
      const customSpace = this.customSpacing.find((s) => s.name === space.name);

      if (customSpace) {
        return customSpace.value && customSpace.value.trim() !== '' ? customSpace.value : space.value;
      }

      return space.value;
    },
    onCheckAllDefaultSpaces() {
      if (this.project && this.project.downloaded) {
        return;
      }

      this.defaultSpacing.forEach((d) => d.enabled = true);
    },
    onToggleSelectedSpaces() {
      if (this.project && this.project.downloaded) {
        return;
      }

      this.defaultSpacing.forEach((d) => d.enabled = !d.enabled);
    },
    onCreateCustomSpaceFromInput() {
      const input = this.customSpaceDefinition.trim();

      if (input === '') {
        return;
      }

      let space = this.customSpacing.find((s) => s.name.toLowerCase() === input.toLowerCase());

      if (space) {
        this.customSpaceDefinition = '';
        return;
      }

      const defaultSpace = this.defaultSpacing.find((s) => s.name.toLowerCase() === input.toLowerCase()) || {};

      space = {
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        name: input,
        value: defaultSpace.value || '',
      };

      this.customSpacing.push(space);
      this.customSpaceDefinition = '';
    },
    onCustomSpaceStateToggled(checked, space) {
      const spaceIndex = this.customSpacing.findIndex((s) => s.id === space.id);

      if (spaceIndex > -1) {
        this.customSpacing.splice(spaceIndex, 1);
      }
    },
  },
};
</script>

<style scoped>

</style>
