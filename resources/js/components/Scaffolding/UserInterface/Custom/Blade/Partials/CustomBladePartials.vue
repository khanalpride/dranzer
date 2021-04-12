<template>
  <div>
    <row v-if="loading">
      <column size="4" offset="4">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column>
        <tabs-manager ref="partialsTabManager"
                      :tabs="tabs"
                      path="config/ui/custom/partials/custom/active" removable @remove="onRemovePartial">
          <template :slot="tab.id" v-for="tab in tabs">
            <custom-blade-partial :partial="tab" :key="tab.id" :views="views" :blueprints="blueprints" :relations="relations"
                                  @change="onPartialChanged($event, tab)" />
          </template>
        </tabs-manager>
      </column>
      <column :push30="tabs.length > 0">
        <simple-button color-class="primary" @click="addPartial">
          <i class="fa fa-plus"></i>
          Add Custom Partial
        </simple-button>
      </column>
    </row>
  </div>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import TabsManager from '@/components/Tabs/TabsManager';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';
import CustomBladePartial from '@/components/Scaffolding/UserInterface/Custom/Blade/Partials/CustomBladePartial';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'CustomBladePartials',
  mixins: [mutations],
  props: {
    blueprints: Array,
    views: Array,
    relations: Array,
  },
  components: {
    IndeterminateProgressBar,
    CustomBladePartial,
    SimpleButton,
    TabsManager,
    Row,
    Column,
  },
  data() {
    // noinspection SpellCheckingInspection
    return {
      loading: false,

      partials: [],
    };
  },
  computed: {
    tabs() {
      return this.partials.map((p) => ({
        id: p.id,
        label: p.name,
      }));
    },
  },
  watch: {
    partials: {
      handler(v) {
        const payload = {
          name: 'Custom Blade Partials',
          path: 'ui/custom/partials/custom',
          value: v,
        };

        this.mutate(payload);
      },
    },
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;

    if (!this.partials.length) {
      this.addPartial();
    }
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'ui/custom/partials/custom' });
      this.partials = data.value || this.partials;
    },
    getModelColumnsSubstitutions(model) {
      const { columns } = model;

      const subs = columns.filter((c) => (this.showUnsigned ? true : (c.name.indexOf('_') < 0 && !c.attributes.us && !c.attributes.ai)))
        .map((c) => `${this.str.lcFirst(this.str.studly(model.modelName))}.${c.name}`);

      const dateColumns = columns.filter((c) => c.type.toLowerCase().indexOf('date') > -1 || c.type.toLowerCase().indexOf('time') > -1);

      dateColumns.forEach((d) => {
        subs.push(`${this.str.lcFirst(this.str.studly(model.modelName))}.${d.name}.diffForHumans()`);
      });

      return subs;
    },

    addPartial() {
      const partial = {
        id: `P${Math.round(Math.random() * Number.MAX_SAFE_INTEGER)}`,
        name: 'custom.partial',
      };

      this.partials.push(partial);

      this.$nextTick(() => {
        if (this.$refs.partialsTabManager) {
          this.$refs.partialsTabManager.activateTabByIndex(this.partials.length - 1);
        }
      });
    },

    onRemovePartial(partialId) {
      const index = this.partials.findIndex((p) => p.id === partialId);
      if (index > -1) {
        this.partials.splice(index, 1);
        this.deleteMutation(`ui/custom/partials/custom/${partialId}`);

        if (this.$refs.partialsTabManager) {
          this.$refs.partialsTabManager.activateTabByIndex(this.partials.length - 1);
        }
      }
    },

    onPartialChanged(update, partial) {
      const pIndex = this.partials.findIndex((p) => p.id === partial.id);
      if (pIndex > -1) {
        this.partials[pIndex].name = update.name.trim() === '' ? 'custom.partial' : update.name;
      }
    },
  },
};
</script>

<style scoped>

</style>
