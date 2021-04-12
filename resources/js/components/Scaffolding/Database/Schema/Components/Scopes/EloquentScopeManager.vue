<template>
  <content-container col-size="10" col-offset="1">
    <row>
      <column>
        <pg-input v-model="definition"
                  placeholder="Enter scope name (e.g. scopeProject or simply project)..."
                  @keydown.native.enter="onAddScopeFromInput" />
      </column>

      <column push20 v-if="presets.length">
        <row>
          <column>
            <form-input-title title="Presets" no-bottom-padding />
            <separator />
          </column>
          <column size="3" :key="preset.id" v-for="preset in presets">
            <pg-check-box no-margin
                          :label="preset.scopedName"
                          :value="isScopeEnabled(preset.scopedName)"
                          @change="onPresetToggled($event, preset)" />
          </column>
        </row>
      </column>

      <column v-if="scopes.length">
        <row>
          <column>
            <separator />
            <form-input-title title="Enabled Scopes" no-bottom-padding />
            <separator />
          </column>
          <column size="3" :key="scope.id" v-for="scope in scopes">
            <pg-check-box no-margin
                          :label="scope.name"
                          :key="scope.id"
                          :value="isScopeEnabled(scope.name)"
                          @change="onScopeStateToggled($event, scope)" />
          </column>
        </row>
      </column>
    </row>
  </content-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgInput from '@/components/Forms/PgInput';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import ContentContainer from '@/components/Content/ContentContainer';
import mutations from '@/mixins/mutations';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import Separator from '@/components/Layout/Separator';

export default {
  name: 'EloquentScopeManager',
  mixins: [mutations],
  props: {
    blueprint: {},
  },
  components: {
    Separator,
    FormInputTitle,
    PgCheckBox,
    PgInput,
    Row,
    Column,
    ContentContainer,
  },
  data() {
    return {
      definition: '',

      scopes: [],
    };
  },
  computed: {
    presets() {
      return !this.blueprint
        ? []
        : this.blueprint.columns.map(
          (c) => ({
            id: c.id,
            name: c.name,
            scopedName: `scope${this.str.studly(c.name)}`,
          }),
        );
    },
  },
  watch: {
    scopes: {
      handler(v) {
        this.persistScopes(v);
        this.$emit('count-changed', this.scopes.length);
      },
      deep: true,
    },
  },
  async created() {
    await this.sync();
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: `eloquent/scopes/${this.blueprint.id}` });
      this.scopes = data.value || [];
    },
    isScopeEnabled(scopeName) {
      const scope = this.scopes.find((s) => s.name.toLowerCase() === scopeName.toLowerCase());
      return scope !== undefined;
    },
    addScope(data = {}) {
      const scope = {
        id: `S${Math.round(Math.random() * Number.MAX_SAFE_INTEGER)}`,
        column: data.column || null,
        name: data.name || '',
      };
      this.scopes.push(scope);
    },
    persistScopes(scopes) {
      const payload = {
        name: 'Scopes',
        path: `eloquent/scopes/${this.blueprint.id}`,
        value: scopes || this.scopes,
      };

      this.mutate(payload);
    },
    onPresetToggled(checked, preset) {
      const scopeIndex = this.scopes.findIndex((s) => s.name.toLowerCase() === preset.scopedName.toLowerCase());

      if (checked) {
        if (scopeIndex < 0) {
          this.addScope({ name: preset.scopedName, column: { id: preset.id, name: preset.name } });
        }
      } else if (scopeIndex > -1) {
        this.scopes.splice(scopeIndex, 1);
      }
    },
    onAddScopeFromInput() {
      const input = this.definition.trim();

      if (input === '') {
        return;
      }

      const scopes = input.split(',');

      scopes.forEach((scope) => {
        const scopeName = scope.toLowerCase().indexOf('scope') > -1 ? scope : `scope${this.str.studly(scope)}`;

        if (!this.rgx.isNumericAlphaNumericOnly(scopeName)) {
          return false;
        }

        const scopeIndex = this.scopes.findIndex((s) => s.name.toLowerCase() === scopeName.toLowerCase());

        if (scopeIndex > -1) {
          return false;
        }

        const column = this.blueprint.columns.find(
          (c) => this.str.snakeCase(c.name).indexOf(
            this.str.snakeCase(scopeName.replace('scope', '')),
          ) > -1,
        );

        this.addScope({ name: scopeName, column: column ? { id: column.id, name: column.name } : {} });

        return true;
      });

      this.definition = '';
    },

    onScopeStateToggled(active, scope) {
      this.scopes = this.scopes.filter((s) => s.id !== scope.id);
    },
  },
};
</script>

<style scoped>

</style>
