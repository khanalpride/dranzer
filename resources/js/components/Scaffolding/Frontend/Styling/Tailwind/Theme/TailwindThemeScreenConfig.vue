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
          You define your project's breakpoints in the
          <span class="text-primary" style="opacity: 1 !important;">theme.screens</span>
          section of your
          <span class="text-primary" style="opacity: 1 !important;">tailwind.config.js</span> file.
          The keys are your screen names (used as the prefix for the responsive utility variants Tailwind generates,
          like <span class="text-primary" style="opacity: 1 !important;">md:text-center</span>),
          and the values are the <span class="text-primary" style="opacity: 1 !important;">min-width</span>
          where that breakpoint should start.
        </blockquote>
        <separator />
      </column>
      <column>
        <pg-input v-model="definition"
                  placeholder="Enter screen definition..."
                  @keydown.enter.native="onCreateScreenFromInput" />
      </column>
      <column :key="screen.id" v-for="screen in screens">
        <row>
          <column>
            <pg-check-box no-margin
                          class="p-t-5"
                          :value="hasEnabledBreakpoints(screen)"
                          color-class="green"
                          :label="screen.alias" @change="onScreenStateChanged($event, screen)" />
          </column>
          <column size="10" class="m-l-30">
            <row :key="breakpoint.id" v-for="breakpoint in screen.breakpoints">
              <column>
                <pg-check-box :value="1" label="Enabled" @change="onBreakpointStateChanged($event, screen, breakpoint)" />
              </column>
              <column size="10" class="m-l-30">
                <row>
                  <column size="2">
                    <pg-check-box no-margin class="p-b-5" v-model="breakpoint.min" label="Min" />
                    <pg-input :disabled="!breakpoint.min" v-model="breakpoint.minBreakpoint" />
                  </column>
                  <column size="2">
                    <pg-check-box no-margin class="p-b-5" v-model="breakpoint.max" label="Max" />
                    <pg-input :disabled="!breakpoint.max" v-model="breakpoint.maxBreakpoint" />
                  </column>
                </row>
              </column>
            </row>
            <row push5 v-if="screen.breakpoints.length < 3">
              <column size="10" class="m-l-30">
                <a href="#"
                   class="link small text-primary"
                   @click.prevent="addBreakpoint(screen)">
                  New Breakpoint
                </a>
              </column>
            </row>
          </column>
        </row>
      </column>
    </row>
  </div>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgInput from '@/components/Forms/PgInput';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Separator from '@/components/Layout/Separator';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import { mapState } from 'vuex';

export default {
  name: 'TailwindThemeScreenConfig',
  mixins: [mutations],
  components: {
    IndeterminateProgressBar,
    Separator,
    Row,
    Column,
    PgInput,
    PgCheckBox,
  },
  data() {
    return {
      loading: false,

      ready: false,

      screens: [],

      definition: '',
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  watch: {
    screens: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({ name: 'Tailwind Screens', path: 'tailwind/screens', value: v });
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;

    await this.sync();

    this.loading = false;

    if (!this.screens.length) {
      this.addScreen({ alias: 'sm', minBreakpoint: '640px' });
      this.addScreen({ alias: 'md', minBreakpoint: '768px' });
      this.addScreen({ alias: 'lg', minBreakpoint: '1024px' });
      this.addScreen({ alias: 'xl', minBreakpoint: '1280px' });
      this.addScreen({ alias: '2xl', minBreakpoint: '1536px' });
    }

    this.$nextTick(() => {
      this.ready = true;
    });
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'tailwind/screens' });
      this.screens = data.value || [];
    },
    addScreen(data = {}) {
      this.screens.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        alias: data.alias || '',
        breakpoints: data.breakpoints || [{
          min: data.min || true,
          max: data.max || false,
          minBreakpoint: data.minBreakpoint || '',
          maxBreakpoint: data.maxBreakpoint || '',
        }],
      });
    },
    addBreakpoint(screen) {
      if (this.project && this.project.downloaded) {
        return;
      }

      screen.breakpoints.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        alias: '',
        breakpoints: [],
      });
    },
    hasEnabledBreakpoints(screen) {
      return screen.breakpoints.length > 0;
    },
    onCreateScreenFromInput() {
      const input = this.definition.trim();

      if (input === '') {
        this.definition = '';
        return;
      }

      const sIndex = this.screens.findIndex((s) => s.alias.toLowerCase() === input.toLowerCase());

      if (sIndex > -1) {
        this.definition = '';
        return;
      }

      const defaults = [
        {
          sm: '640px',
        },
        {
          md: '768px',
        },
        {
          lg: '1024px',
        },
        {
          xl: '1280px',
        },
        {
          '2xl': '1536px',
        },
      ];

      const minBreakpoint = defaults.find((d) => Object.keys(d).includes(input)) || {};

      this.addScreen({ alias: input, breakpoints: [{ min: true, minBreakpoint: minBreakpoint[input] }] });

      this.definition = '';
    },
    onBreakpointStateChanged(active, screen, breakpoint) {
      if (!active) {
        const bIndex = screen.breakpoints.findIndex((b) => b.id === breakpoint.id);
        if (bIndex > -1) {
          screen.breakpoints.splice(bIndex, 1);
          if (!screen.breakpoints.length) {
            const sIndex = this.screens.findIndex((s) => s.id === screen.id);
            if (sIndex > -1) {
              this.screens.splice(sIndex, 1);
            }
          }
        }
      }
    },
    onScreenStateChanged(active, screen) {
      if (!active) {
        const sIndex = this.screens.findIndex((s) => s.id === screen.id);
        if (sIndex > -1) {
          this.screens.splice(sIndex, 1);
        }
      }
    },
  },
};
</script>

<style scoped>

</style>
