<template>
  <div>
    <row v-if="loading">
      <column size="4" offset="4">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column>
        <basic-content-section heading="Partial Name">
          <row>
            <column size="4" offset="4">
              <pg-input v-model="partialName" placeholder="e.g. post, post_index, posts.index" />
            </column>
          </row>
        </basic-content-section>
      </column>
      <column>
        <basic-content-section heading="Data Source(s)" prepend-separator>
          <row>
            <column size="3" :key="model.id" v-for="model in visibleModels">
              <pg-check-box no-margin
                            :value="dataSources.includes(model.modelName)"
                            :label="model.modelName" @change="onDataSourceModelToggled($event, model)" />
            </column>
          </row>
        </basic-content-section>
      </column>
      <column>
        <basic-content-section heading="HTML Fragment" prepend-separator>
          <codemirror ref="editor" v-model="code" :options="options"
                      @cursorActivity="onCursorActivity" @beforeChange="onBeforeDocChange" />
        </basic-content-section>
      </column>
      <column v-if="selection">
        <basic-content-section heading="Available Substitutions" prepend-separator>
          <row>
            <column centered>
              <pg-check-box v-model="showUnsigned" no-margin centered label="Show unsigned and underscore columns" />
            </column>
            <column>
              <separator />
            </column>
          </row>
          <row v-if="routes.length">
            <column>
              <basic-content-section :centered="false" heading="Routes">
                <row>
                  <column>
                    <a href="#"
                       class="text-info"
                       :class="{'m-l-10': index > 0}"
                       @click.prevent="onSubstitute(getRouteSubstitution(route), 'route')"
                       :key="route.id"
                       v-for="(route, index) in routes">
                      {{ route.name }}
                    </a>
                  </column>
                </row>
              </basic-content-section>
            </column>
          </row>
          <row :key="group.model" v-for="group in groupedSubstitutions">
            <column>
              <basic-content-section :centered="false" :heading="`${group.model} Model`" :prepend-separator="routes.length > 0">
                <row>
                  <column size="3" :key="substitution" v-for="substitution in group.substitutions">
                    <a href="#"
                       class="text-info"
                       @click.prevent="onSubstitute(substitution)">
                      {{ substitution }}
                    </a>
                  </column>
                </row>
              </basic-content-section>
            </column>
          </row>
          <row>
            <column>
              <basic-content-section heading="Custom Substitution Presets" prepend-separator>
                <row>
                  <column :key="`P${preset.id}`" v-for="preset in substitutionPresets">
                    <form-input-group compact>
                      <simple-button color-class="primary" @click="addPreset">
                        <i class="fa fa-plus"></i>
                      </simple-button>
                      <pg-input v-model="preset.text" placeholder="Custom substitution preset..." />
                      <simple-button color-class="danger" @click="removePreset(preset)">
                        <i class="fa fa-close"></i>
                      </simple-button>
                    </form-input-group>
                  </column>
                  <column push10>
                    <a href="#"
                       class="text-info"
                       :class="{'m-l-10': index > 0}"
                       :key="preset.id"
                       @click.prevent="onSubstitute(preset.text, 'custom')"
                       v-for="(preset, index) in substitutionPresets">
                      {{ str.ellipse(preset.text, 50) }}
                    </a>
                  </column>
                </row>
              </basic-content-section>
            </column>
          </row>
        </basic-content-section>
      </column>
    </row>
  </div>
</template>

<script>
import codemirror from '@/components/VueCodeMirror/codemirror';
import 'codemirror/lib/codemirror.css';
import 'codemirror/mode/htmlmixed/htmlmixed';
import 'codemirror/addon/selection/active-line';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import BasicContentSection from '@/components/Content/BasicContentSection';
import Separator from '@/components/Layout/Separator';
import PgInput from '@/components/Forms/PgInput';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'CustomBladePartial',
  mixins: [mutations],
  props: {
    partial: {},
    blueprints: Array,
    views: Array,
    relations: Array,
  },
  components: {
    IndeterminateProgressBar,
    SimpleButton,
    FormInputGroup,
    PgInput,
    Separator,
    BasicContentSection,
    PgCheckBox,
    Row,
    Column,
    codemirror,
  },
  data() {
    // noinspection SpellCheckingInspection
    return {
      loading: false,

      partialName: this.partial.label || '',

      code: '',

      selection: null,
      selectedText: null,

      dataSources: [],

      showUnsigned: false,

      substitutionPresets: [],

      options: {
        mode: 'htmlmixed',
        lineWrapping: true,
        lineNumbers: true,
        maxLength: 1024 * 40,
        styleActiveLine: { nonEmpty: true },
      },
    };
  },
  computed: {
    persistable() {
      return {
        name: this.partialName.trim(),
        code: this.code,
        dataSources: this.dataSources,
        showUnsigned: this.showUnsigned,
        substitutionPresets: this.substitutionPresets,
      };
    },

    visibleModels() {
      return this.blueprints.filter((s) => s.visible !== false);
    },

    routes() {
      return (this.views || [])
        .filter((v) => v.controller)
        .map((v) => (
          {
            id: v.id,
            name: `show.${this.str.snakeCase(v.name).replace(/_/g, '.')}`,
            params: [
              {
                name: `${v.name}`,
                value: `${v.name}`,
              },
            ],
          }
        ));
    },

    editor() {
      return this.$refs.editor.codemirror;
    },

    groupedSubstitutions() {
      return this.visibleModels.filter((v) => this.dataSources.indexOf(v.modelName) > -1)
        .map((m) => ({
          model: m.modelName,
          substitutions: this.getModelColumnsSubstitutions(m).concat(this.getModelRelationSubstitutions(m)),
          relations: this.getModelRelationSubstitutions(m),
        }));
    },
  },
  watch: {
    persistable: {
      handler(v) {
        const payload = {
          name: 'Custom Blade Partial',
          path: `ui/custom/partials/custom/${this.partial.id}`,
          value: v,
        };

        this.$emit('change', v);
        this.mutate(payload);
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;

    await this.sync();

    if (!this.substitutionPresets.length) {
      this.addPreset();
    }

    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: `ui/custom/partials/custom/${this.partial.id}` });
      if (data.value) {
        const { value } = data;
        this.partialName = value.name || this.partialName;
        this.code = value.code || this.code;
        this.dataSources = value.dataSources || this.dataSources;
        this.showUnsigned = value.showUnsigned !== undefined ? value.showUnsigned : false;
        this.substitutionPresets = value.substitutionPresets || this.substitutionPresets;
      }
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

    getRouteSubstitution(route) {
      const params = route.params.map((p) => `'${p.name}' => $${p.value}`).join(', ');
      return `{{ route('${route.name}', [${params}]) }}`;
    },

    getModelRelations(model) {
      return this.relations.filter((r) => r.source.id === model.id).map((r) => {
        const sourceId = r.source.id;
        const relatedId = r.related.id;
        const { type } = r;

        const source = this.blueprints.find((s) => s.id === sourceId);
        const related = this.blueprints.find((s) => s.id === relatedId);

        if (!source || !related) {
          return null;
        }

        let relationName = this.str.lcFirst(this.str.studly(related.modelName));

        if (type === 'hasMany' || r.intermediateTable || r.columns) {
          relationName = this.str.lcFirst(this.str.studly(this.str.pluralize(related.modelName)));
        }

        return {
          id: r.id,
          source: sourceId,
          related: relatedId,
          type,
          relationName,
        };
      }).filter((r) => r);
    },

    getModelRelationSubstitutions(model) {
      const relations = this.getModelRelations(model);

      const subs = [];

      relations.forEach((r) => {
        if (r.type === 'hasMany' || r.type === 'hasManyThrough' || r.type === 'manyToMany') {
          subs.push(`${this.str.lcFirst(this.str.studly(model.modelName))}.${r.relationName}`);
          subs.push(`${this.getModelVarName(model.modelName)}.${r.relationName}.count()`);
        } else {
          subs.push(`${this.getModelVarName(model.modelName)}.${r.relationName}`);

          subs.concat(this.getModelColumnsSubstitutions(model));

          const related = this.blueprints.find((s) => s.id === r.related);

          if (related) {
            const relatedColumns = (related.columns || []).filter(
              (c) => (this.showUnsigned ? true : (c.name.indexOf('_') < 0 && !c.attributes.us && !c.attributes.ai)),
            );

            relatedColumns.forEach((rC) => {
              subs.push(`${this.getModelVarName(model.modelName)}.${this.getModelVarName(related.modelName)}.${rC.name}`);
            });
          }
        }
      });

      return subs;
    },

    getModelVarName(modelName) {
      return this.str.lcFirst(this.str.studly(modelName));
    },

    addPreset() {
      this.substitutionPresets.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        text: '',
      });
    },

    removePreset(preset) {
      const index = this.substitutionPresets.findIndex((p) => p.id === preset.id);
      if (index > -1) {
        this.substitutionPresets.splice(index, 1);

        if (!this.substitutionPresets.length) {
          this.addPreset();
        }
      }
    },

    onCursorActivity(e) {
      this.selection = e.doc.sel;
      this.selectedText = this.editor.getSelection();
    },

    onSubstitute(substitution, type = 'model') {
      if (!this.selection || !this.selection.ranges || !this.selection.ranges.length) {
        return;
      }

      const range = this.selection.ranges[0];

      if (!range || !range.anchor || !range.head) {
        return;
      }

      let substitutionToVar = null;

      if (type === 'model') {
        const expanded = substitution.split('.').join('->');
        substitutionToVar = `{{ $${expanded} }}`;
      }

      if (type === 'custom' || type === 'route') {
        substitutionToVar = substitution;
      }

      this.editor.replaceRange(
        substitutionToVar,
        { line: range.anchor.line, ch: range.anchor.ch },
        { line: range.head.line, ch: range.head.ch },
      );
    },

    onDataSourceModelToggled(active, model) {
      if (active) {
        if (!this.dataSources.includes(model.modelName)) {
          this.dataSources.push(model.modelName);
        }
      } else {
        const index = this.dataSources.findIndex((s) => s === model.modelName);
        if (index > -1) {
          this.dataSources.splice(index, 1);
        }
      }
    },

    onBeforeDocChange(instance, change) {
      return this.enforceMaxLength(instance, change);
    },

    /**
     * https://github.com/codemirror/CodeMirror/issues/821#issuecomment-36967065
     *
     * @param cm
     * @param change
     * @returns {boolean}
     */
    enforceMaxLength(cm, change) {
      const maxLength = cm.getOption('maxLength');
      if (maxLength && change.update) {
        let str = change.text.join('\n');
        let delta = str.length - (cm.indexFromPos(change.to) - cm.indexFromPos(change.from));
        if (delta <= 0) { return true; }
        delta = cm.getValue().length + delta - maxLength;
        if (delta > 0) {
          str = str.substr(0, str.length - delta);
          change.update(change.from, change.to, str.split('\n'));
        }
      }
      return true;
    },
  },
};
</script>

<style scoped>

</style>
