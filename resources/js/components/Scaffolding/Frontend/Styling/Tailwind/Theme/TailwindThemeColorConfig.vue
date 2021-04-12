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
          Tailwind includes an expertly-crafted default color palette out-of-the-box
          that is a great starting point if you don't have your own specific branding in mind.
          But when you do need to customize your palette,
          you can configure your colors under the colors key in the theme section of your tailwind.config.js file.
        </blockquote>
        <separator />
      </column>
      <column>
        <basic-content-section heading="Default Color Palette">
          <row>
            <column centered>
              <pg-check-box centered no-margin v-model="useDefaultColors" label="Use Default Color Palette" />
              <pg-check-box centered no-margin v-model="showDefaultColors" label="Show Default Color Palette" />
              <separator />
            </column>
            <column>
              <row>
                <column>
                  <a href="#"
                     class="small"
                     :class="{'hint-text': !useDefaultColors}"
                     @click.prevent="onCheckAllDefaultColors">
                    Check All
                  </a>
                  <a href="#"
                     class="m-l-5 small"
                     :class="{'hint-text': !useDefaultColors}"
                     @click.prevent="onToggleSelectedColors">
                    Toggle Selection
                  </a>
                </column>
                <column>
                  <separator />
                </column>
              </row>
              <row>
                <column size="2" :key="color.id" v-for="color in defaultColors">
                  <pg-check-box v-model="color.enabled" no-margin :label="color.name" v-if="useDefaultColors" />
                  <pg-check-box disabled no-margin :label="color.name" v-else />
                </column>
              </row>
              <row v-if="showDefaultColors">
                <column push10 centered>
                  <img :src="asset('images/tailwind-colors.png')" alt class="img-fluid" />
                </column>
              </row>
            </column>
          </row>
        </basic-content-section>
        <basic-content-section heading="Transparent and Current" prepend-separator>
          <row>
            <column centered>
              <pg-check-box centered no-margin v-model="transparent" label="Transparent" />
              <pg-check-box centered no-margin v-model="current" label="Current" />
            </column>
          </row>
        </basic-content-section>
        <basic-content-section heading="Custom Colors" prepend-separator>
          <row>
            <column>
              <pg-input v-model="customColorDefinition"
                        placeholder="Enter custom color name or definition..."
                        @keydown.enter.native="onCreateCustomColorFromInput" />
            </column>
            <column push10 v-if="customColors.length">
              <row :key="color.id" v-for="color in customColors">
                <column>
                  <form-input-group>
                    <el-color-picker :disabled="project && project.downloaded" v-model="color.color" />
                    <pg-check-box :value="1"
                                  no-margin
                                  class="color-name-checkbox"
                                  :label="color.name" @change="onColorStateToggled($event, color)" />
                    <pg-input v-model="color.newShadeName"
                              class="m-l-5"
                              style="width:10%"
                              placeholder="Enter shade name or definition..."
                              @keydown.enter.native="onCreateShadeFromInput(color)" />
                  </form-input-group>
                </column>
                <column size="10" class="m-l-45">
                  <form-input-group v-if="color.shades.length">
                    <el-color-picker :disabled="project && project.downloaded" :value="color.color" />
                    <pg-check-box :value="1"
                                  no-margin
                                  disabled
                                  class="color-name-checkbox"
                                  label="DEFAULT" />
                  </form-input-group>
                  <form-input-group :key="shade.id" v-for="shade in color.shades">
                    <el-color-picker :disabled="project && project.downloaded" v-model="shade.color" />
                    <pg-check-box :value="1"
                                  no-margin
                                  class="color-name-checkbox"
                                  :label="shade.name"
                                  @change="onShadeStateToggled($event, shade, color)" />
                  </form-input-group>
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
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import BasicContentSection from '@/components/Content/BasicContentSection';
import Separator from '@/components/Layout/Separator';
import PgInput from '@/components/Forms/PgInput';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import { mapState } from 'vuex';

export default {
  name: 'TailwindThemeColorConfig',
  mixins: [mutations],
  components: {
    IndeterminateProgressBar,
    FormInputGroup,
    PgInput,
    Separator,
    BasicContentSection,
    PgCheckBox,
    Row,
    Column,
  },
  data() {
    return {
      loading: false,

      ready: false,

      useDefaultColors: true,
      showDefaultColors: false,

      transparent: false,
      current: false,

      customColorDefinition: '',

      webColors: {
        white: '#FFFFFF',
        silver: '#C0C0C0',
        gray: '#808080',
        black: '#000000',
        red: '#FF0000',
        maroon: '#800000',
        yellow: '#FFFF00',
        olive: '#808000',
        lime: '#00FF00',
        green: '#008000',
        aqua: '#00FFFF',
        teal: '#008080',
        blue: '#0000FF',
        navy: '#000080',
        fuchsia: '#FF00FF',
        purple: '#800080',
      },

      customColors: [],

      defaultColors: [
        {
          id: 'colors.blueGray',
          name: 'Blue Gray',
          enabled: true,
        },
        {
          id: 'colors.coolGray',
          name: 'Cool Gray',
          enabled: true,
        },
        {
          id: 'colors.gray',
          name: 'Gray',
          enabled: true,
        },
        {
          id: 'colors.trueGray',
          name: 'True Gray',
          enabled: true,
        },
        {
          id: 'colors.warmGray',
          name: 'Warm Gray',
          enabled: true,
        },
        {
          id: 'colors.red',
          name: 'Red',
          enabled: true,
        },
        {
          id: 'colors.orange',
          name: 'Orange',
          enabled: true,
        },
        {
          id: 'colors.amber',
          name: 'Amber',
          enabled: true,
        },
        {
          id: 'colors.yellow',
          name: 'Yellow',
          enabled: true,
        },
        {
          id: 'colors.lime',
          name: 'Lime',
          enabled: true,
        },
        {
          id: 'colors.green',
          name: 'Green',
          enabled: true,
        },
        {
          id: 'colors.emerald',
          name: 'Emerald',
          enabled: true,
        },
        {
          id: 'colors.teal',
          name: 'Teal',
          enabled: true,
        },
        {
          id: 'colors.cyan',
          name: 'Cyan',
          enabled: true,
        },
        {
          id: 'colors.lightBlue',
          name: 'Light Blue',
          enabled: true,
        },
        {
          id: 'colors.blue',
          name: 'Blue',
          enabled: true,
        },
        {
          id: 'colors.indigo',
          name: 'Indigo',
          enabled: true,
        },
        {
          id: 'colors.violet',
          name: 'Violet',
          enabled: true,
        },
        {
          id: 'colors.purple',
          name: 'Purple',
          enabled: true,
        },
        {
          id: 'colors.fuchsia',
          name: 'Fuchsia',
          enabled: true,
        },
        {
          id: 'colors.pink',
          name: 'Pink',
          enabled: true,
        },
        {
          id: 'colors.rose',
          name: 'Rose',
          enabled: true,
        },
      ],
    };
  },
  watch: {
    customColors: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({
          name: 'Tailwind Custom Colors',
          path: 'tailwind/colors/custom',
          value: v.map((cc) => ({
            id: cc.id,
            name: cc.name,
            color: cc.color,
            shades: cc.shades,
          })),
        });
      },
      deep: true,
    },
    defaultColors: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({ name: 'Tailwind Default Colors', path: 'tailwind/colors/default', value: v });
      },
      deep: true,
    },
    config: {
      handler(v) {
        if (!this.ready) {
          return;
        }

        this.mutate({ name: 'Tailwind Config', path: 'tailwind/colors/config', value: v });
      },
    },
  },
  computed: {
    ...mapState('project', ['project']),

    config() {
      return {
        useDefaultColors: this.useDefaultColors,
        transparent: this.transparent,
        current: this.current,
      };
    },
  },
  async created() {
    this.loading = true;

    await this.syncDefaultColors();
    await this.syncCustomColors();
    await this.syncConfig();

    this.loading = false;

    this.$nextTick(() => {
      this.ready = true;
    });
  },
  methods: {
    async syncDefaultColors() {
      const { data } = await this.mutation({ path: 'tailwind/colors/default' });
      this.defaultColors = data.value || this.defaultColors;
    },
    async syncCustomColors() {
      const { data } = await this.mutation({ path: 'tailwind/colors/custom' });
      this.customColors = data.value || this.customColors;
    },
    async syncConfig() {
      const { data } = await this.mutation({ path: 'tailwind/colors/config' });

      const value = data.value || {};

      this.useDefaultColors = value.useDefaultColors !== undefined ? value.useDefaultColors : this.useDefaultColors;
      this.transparent = value.transparent !== undefined ? value.transparent : this.transparent;
      this.current = value.current !== undefined ? value.current : this.current;
    },
    onCheckAllDefaultColors() {
      if (!this.useDefaultColors || (this.project && this.project.downloaded)) {
        return;
      }

      this.defaultColors.forEach((d) => d.enabled = true);
    },
    onToggleSelectedColors() {
      if (!this.useDefaultColors || (this.project && this.project.downloaded)) {
        return;
      }

      this.defaultColors.forEach((d) => d.enabled = !d.enabled);
    },
    onCreateCustomColorFromInput() {
      const input = this.customColorDefinition.trim();

      if (input === '') {
        return;
      }

      let color = this.customColors.find((c) => c.name.toLowerCase() === input.toLowerCase());

      if (color) {
        this.customColorDefinition = '';
        return;
      }

      const webColorName = Object.keys(this.webColors).find((name) => name.toLowerCase() === input.toLowerCase());

      const hex = webColorName ? this.webColors[input.toLowerCase()] : '';

      color = {
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        name: input,
        color: hex,
        shades: [],
        newShadeName: '',
      };

      this.customColors.push(color);

      // if (this.addDefault) {
      //   color.shades.push({
      //     id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
      //     name: 'DEFAULT',
      //     color: hex,
      //   });
      // }

      this.customColorDefinition = '';
    },
    onCreateShadeFromInput(color) {
      const input = color.newShadeName.trim();

      if (input === '') {
        return;
      }

      const shade = color.shades.find((s) => s.name.toLowerCase() === input.toLowerCase());

      if (shade) {
        color.newShadeName = '';
        return;
      }

      const webColorName = Object.keys(this.webColors).find((name) => name.toLowerCase() === input.toLowerCase());

      const hex = webColorName ? this.webColors[input.toLowerCase()] : color.color;

      color.shades.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        name: input,
        color: hex,
      });

      color.newShadeName = '';
    },

    onColorStateToggled(checked, color) {
      const colorIndex = this.customColors.findIndex((c) => c.id === color.id);

      if (colorIndex > -1) {
        this.customColors.splice(colorIndex, 1);
      }
    },

    onShadeStateToggled(checked, shade, color) {
      const shadeIndex = color.shades.findIndex((s) => s.id === shade.id);
      if (shadeIndex > -1) {
        color.shades.splice(shadeIndex, 1);
      }
    },
  },
};
</script>

<style scoped>
.color-name-checkbox {
  padding-top:8px;
  padding-left:5px;
}
</style>
