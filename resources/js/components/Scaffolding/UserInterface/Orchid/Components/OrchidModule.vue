<template>
    <div>
        <row v-if="loading">
            <column size="4" offset="4">
                <indeterminate-progress-bar/>
            </column>
        </row>
        <content-container :loading="loading">
            <basic-content-section heading="Module Layout Configuration">
                <row>
                    <column centered>
                        <pg-check-box no-margin centered v-model="showInNav" label="Show In Navigation Bar"/>
                        <separator/>
                    </column>
                    <column centered size="6" offset="3">
                        <form-input-title title="Navigation Entry (Icon and Parent Sidebar Section)"/>
                        <form-input-group class="m-t-10" style="display: flex; justify-content: center">
                            <template slot="prepend">
                                <img alt class="m-l-5 hint-text" :src="`orchid/svg/${navIcon}.svg`" width="24"
                                     v-if="navIcon"/>
                            </template>
                            <simple-select filterable
                                           class="m-l-10"
                                           v-model="navIcon"
                                           :disabled="!showInNav"
                                           :entities="icons">
                                <template slot-scope="{ entity }">
                                    <el-option :key="entity.name" :label="entity.name" :value="entity.name"/>
                                </template>
                            </simple-select>
                            <simple-select filterable
                                           class="m-l-10"
                                           v-model="section"
                                           :entities="sections"
                                           :disabled="!showInNav || !sections">
                                <template slot-scope="{ entity }">
                                    <el-option :key="entity.name" :label="entity.name" :value="entity.name"/>
                                </template>
                            </simple-select>
                        </form-input-group>
                    </column>
                </row>
            </basic-content-section>
            <basic-content-section prepend-separator heading="Full Text Search Configuration">
                <row>
                    <column centered>
                        <pg-check-box v-model="fullTextSearch" no-margin centered label="Enable Full Text Search"/>
                        <separator/>
                    </column>
                    <column>
                        <row>
                            <column size="4" offset="4">
                                <form-input-title title="Index Name"/>
                                <pg-input :disabled="!fullTextSearch" v-model="searchableAs"/>
                            </column>
                            <column push15 size="6" offset="3">
                                <row>
                                    <column size="6">
                                        <form-input-title>
                                            <i class="fa fa-exclamation-triangle text-danger bold"
                                               v-if="fullTextSearch && !titleColumn"></i> Title Column
                                        </form-input-title>
                                        <selectable-table-columns :columns="columns" filterable full-width class="m-t-5"
                                                                  :disabled="!fullTextSearch" v-model="titleColumn"/>
                                    </column>
                                    <column size="6">
                                        <form-input-title title="Sub-Title Column"/>
                                        <selectable-table-columns :columns="columns" clearable filterable full-width
                                                                  class="m-t-5"
                                                                  :disabled="!fullTextSearch" v-model="subTitleColumn"/>
                                    </column>
                                    <column push10>
                                        <p class="text-info hint-text no-margin small">
                                            <i class="fa fa-info"></i>
                                            These columns constitute the search result item for this model.
                                        </p>
                                    </column>
                                </row>
                            </column>
                        </row>
                    </column>
                </row>
            </basic-content-section>
            <basic-content-section prepend-separator heading="Module Form Configuration">
                <row v-if="columns && columns.length">
                    <column>
                        <a href="#"
                           class="small"
                           :class="{'text-green': reorderColumns}"
                           @click.prevent="onModifyColumnDisplayOrder">
                            <span v-if="!reorderColumns">Modify Column Display Order</span>
                            <span v-else>Done</span>
                        </a>
                        <separator/>
                    </column>
                    <column size="4" offset="4" v-if="reorderColumns">
                        <p class="text-center hint-text text-info"><i class="fa fa-info"></i> Drag and drop columns to
                            re-order</p>
                        <draggable v-model="sortedColumns" group @change="onColumnOrderChanged">
                            <pg-input class="m-t-5" :value="column.name" read-only :key="column.id"
                                      v-for="column in sortedColumns"/>
                        </draggable>
                    </column>
                    <column v-if="reorderColumns">
                        <separator/>
                    </column>
                </row>
                <row :key="column.id" v-for="column in filteredColumns">
                    <column>
                        <column>
                            <pg-check-box no-margin color-class="green" v-model="column.visible">
                                <template slot="label">
                  <span class="bold" :class="{'text-green': column.visible}">
                    {{ column.name }}
                    <span class="text-primary m-l-10"
                          v-if="column.type === 'hasManyRelation'">One To Many Relation</span>
                  </span>
                                </template>
                            </pg-check-box>
                        </column>
                    </column>
                    <column class="m-l-50" v-if="column.visible">
                        <row>
                            <column size="5">
                                <row push10>
                                    <column size="6">
                                        <form-input-title :centered="false" small title="Heading / Label"/>
                                        <pg-input class="input-max-height" v-model="column.layout.controlProps.title"/>
                                    </column>
                                    <column size="6">
                                        <form-input-title :centered="false" small title="Visible"/>
                                        <simple-select multiple
                                                       collapse-tags
                                                       full-width
                                                       placeholder="Not visible"
                                                       :entities="visibilityOptions"
                                                       v-model="column.layout.moduleVisibility"
                                                       @change="onModuleVisibilityUpdated($event, column)">
                                            <template slot-scope="{ entity }">
                                                <el-option :label="entity.label" :value="entity.value"/>
                                            </template>
                                        </simple-select>
                                    </column>
                                </row>
                            </column>
                            <column push10>
                                <tabs-manager :tabs="columnTabs"
                                              :path="`config/admin/modules/${model.id}/columns/${column.id}/tabs/active`">
                                    <template slot="createOrUpdate">
                                        <row>
                                            <column>
                                                <pg-check-box no-margin label="Required"
                                                              color-class="danger"
                                                              v-model="column.required"/>
                                                <pg-check-box no-margin label="Vertically Oriented"
                                                              v-model="column.vertical"/>
                                            </column>
                                            <column>
                                                <separator compact/>
                                            </column>
                                            <column push10 size="10">
                                                <row v-if="column.type !== 'hasManyRelation'">
                                                    <column size="3">
                                                        <form-input-title :centered="false" title="Input Type"
                                                                          class="text-primary small"/>
                                                        <simple-select full-width
                                                                       :entities="controls"
                                                                       v-model="column.layout.control"
                                                                       @change="column.inputControlComponent = getControl(column)">
                                                            <template slot-scope="{ entity }">
                                                                <el-option :key="entity.name" :label="entity.label"
                                                                           :value="entity.name"/>
                                                            </template>
                                                        </simple-select>
                                                    </column>
                                                </row>
                                                <row push5
                                                     v-if="column.visible && column.inputControlComponent && column.type !== 'hasManyRelation'">
                                                    <column push5 class="p-b-10" size="6">
                                                        <component :is="column.inputControlComponent"
                                                                   :table-id="model.id"
                                                                   :table-name="model.tableName"
                                                                   :model="model"
                                                                   :persisted="column.layout.controlProps"
                                                                   :column="column"
                                                                   :columns="columns"
                                                                   :blueprints="blueprints"
                                                                   :relations="relations"
                                                                   @updated="onControlPropsUpdated($event, column)"/>
                                                    </column>
                                                </row>

                                                <row v-if="column.visible && column.type === 'hasManyRelation'">
                                                    <column class="p-b-10" size="6">
                                                        <orchid-has-many-relation :table-id="model.id"
                                                                                  :table-name="model.tableName"
                                                                                  :model="model"
                                                                                  :foreign-model="column.relatedModel"
                                                                                  :persisted="column.layout.controlProps"
                                                                                  :column="column"
                                                                                  @updated="onControlPropsUpdated($event, column)"/>
                                                    </column>
                                                </row>
                                            </column>
                                        </row>
                                    </template>

                                    <template slot="presentation">
                                        <row class="p-b-20">
                                            <column>
                                                <pg-check-box no-margin label="Searchable" v-model="column.searchable"/>
                                                <pg-check-box no-margin label="Sortable" v-model="column.sortable"/>
                                                <pg-check-box no-margin label="Filterable" v-model="column.filterable"/>
                                            </column>

                                            <column push20 class="p-b-10" size="5"
                                                    v-if="getInferredControlType(column) === 'relation'">
                                                <row>
                                                    <column>
                                                        <pg-check-box no-margin
                                                                      v-model="column.layout.listControlProps.copy"
                                                                      label="Same column as Create / Update"/>
                                                    </column>
                                                    <column>
                                                        <component :is="column.inputControlComponent"
                                                                   :table-id="model.id"
                                                                   :table-name="model.tableName"
                                                                   :model="model"
                                                                   :persisted="
                                       column.layout.listControlProps.copy
                                       ? column.layout.controlProps
                                       : (column.layout.listControlProps || column.layout.controlProps)
                                       "
                                                                   :column="column"
                                                                   :columns="columns"
                                                                   :blueprints="blueprints"
                                                                   :relations="relations"
                                                                   :disabled="column.layout.listControlProps.copy"
                                                                   hide-value-column
                                                                   screen="list"
                                                                   hide-info
                                                                   @updated="onControlListPropsUpdated($event, column)"/>
                                                    </column>
                                                </row>
                                            </column>

                                            <column push20 class="p-b-10" size="5"
                                                    v-if="getInferredControlType(column) === 'hasManyRelation'">
                                                <row>
                                                    <column>
                                                        <pg-check-box no-margin
                                                                      v-model="column.layout.listControlProps.copy"
                                                                      label="Same column as Create / Update"/>
                                                    </column>
                                                    <column>
                                                        <component :is="column.inputControlComponent"
                                                                   :table-id="model.id"
                                                                   :table-name="model.tableName"
                                                                   :foreign-model="column.relatedModel"
                                                                   :persisted="
                                       column.layout.listControlProps.copy
                                       ? column.layout.controlProps
                                       : (column.layout.listControlProps || column.layout.controlProps)
                                       "
                                                                   :column="column"
                                                                   :columns="columns"
                                                                   :blueprints="blueprints"
                                                                   :relations="relations"
                                                                   :disabled="column.layout.listControlProps.copy"
                                                                   hide-value-column
                                                                   screen="list"
                                                                   hide-info
                                                                   @updated="onControlListPropsUpdated($event, column)"/>
                                                    </column>
                                                </row>
                                            </column>
                                        </row>
                                    </template>
                                </tabs-manager>
                            </column>
                        </row>
                    </column>
                </row>
            </basic-content-section>
        </content-container>
    </div>
</template>

<script>
import Draggable from 'vuedraggable';
import ContentContainer from '@/components/Content/ContentContainer';
import lodash from 'lodash';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import OrchidInput from '@/components/Scaffolding/UserInterface/Orchid/Components/FormInput/OrchidInput';
import Separator from '@/components/Layout/Separator';
import BasicContentSection from '@/components/Content/BasicContentSection';
import PgInput from '@/components/Forms/PgInput';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import orchidIcons from '@/data/orchid/orchid_icons';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import ToggleButton from '@/components/Forms/Buttons/ToggleButton';
import OrchidDateTime from '@/components/Scaffolding/UserInterface/Orchid/Components/FormInput/OrchidDateTime';
import OrchidTextArea from '@/components/Scaffolding/UserInterface/Orchid/Components/FormInput/OrchidTextArea';
import OrchidCheckbox from '@/components/Scaffolding/UserInterface/Orchid/Components/FormInput/OrchidCheckbox';
import OrchidSelect from '@/components/Scaffolding/UserInterface/Orchid/Components/FormInput/OrchidSelect';
import OrchidBoolean from '@/components/Scaffolding/UserInterface/Orchid/Components/FormInput/OrchidBoolean';
import TabsManager from '@/components/Tabs/TabsManager';
import OrchidRelation from '@/components/Scaffolding/UserInterface/Orchid/Components/FormInput/OrchidRelation';
import OrchidHasManyRelation
  from '@/components/Scaffolding/UserInterface/Orchid/Components/FormInput/OrchidHasManyRelation';
import OrchidQuillEditor from '@/components/Scaffolding/UserInterface/Orchid/Components/FormInput/OrchidQuillEditor';
import SimpleSelect from '@/components/Select/SimpleSelect';
import SelectableTableColumns from '@/components/Select/SelectableTableColumns';

export default {
  name: 'OrchidModule',
  props: {
    model: {},
    sections: Array,
    blueprints: Array,
    eloquentRelations: Array,
  },
  mixins: [mutations],
  components: {
    SelectableTableColumns,
    SimpleSelect,
    OrchidHasManyRelation,
    TabsManager,
    Draggable,
    ToggleButton,
    FormInputGroup,
    IndeterminateProgressBar,
    PgInput,
    BasicContentSection,
    Separator,
    FormInputTitle,
    PgCheckBox,
    Row,
    Column,
    ContentContainer,
  },
  data() {
    return {
      loading: false,

      ready: false,

      initialized: false,

      columns: [],

      sortedColumns: [],

      selectedColumns: [],

      relations: [],

      hasManyRelations: [],

      visibilityOptions: [
        {
          value: 'listing',
          label: 'When Listing',
        },
        {
          value: 'creating',
          label: 'When Creating',
        },
      ],

      controls: [
        {
          name: 'input',
          label: 'Input',
        },
        {
          name: 'textarea',
          label: 'Text Area',
        },
        {
          name: 'htmlEditor',
          label: 'HTML Editor',
        },
        {
          name: 'datetime',
          label: 'Date Time',
        },
        {
          name: 'select',
          label: 'Select / Dropdown',
        },
        {
          name: 'relation',
          label: 'Eloquent Relation',
        },
        {
          name: 'boolean',
          label: 'Boolean',
        },
        {
          name: 'checkbox',
          label: 'Checkbox',
        },
      ],

      showInNav: true,
      navIcon: 'doc',
      icons: [],
      section: null,

      title: '',
      desc: '',

      fullTextSearch: true,
      searchableAs: '',
      titleColumn: null,
      subTitleColumn: null,

      reorderColumns: false,
    };
  },
  computed: {
    filteredColumns() {
      return this.columns;
    },
    moduleTitle: {
      get() {
        return this.title || this.str.humanize(this.str.pluralize(this.model.modelName));
      },
      set(v) {
        this.title = v;
      },
    },
    moduleDescription: {
      get() {
        return this.desc || `View and manage ${this.moduleTitle.toLowerCase()}`;
      },
      set(v) {
        this.desc = v;
      },
    },
    columnTabs() {
      return [
        {
          id: 'createOrUpdate',
          label: 'Create / Update',
        },
        {
          id: 'presentation',
          label: 'Listing / Presentation',
        },
      ];
    },
    visibleColumns() {
      return this.columns.filter((col) => col.visible);
    },
    persistable() {
      return {
        moduleId: this.model.id,
        showInNav: this.showInNav,
        navIcon: this.navIcon,
        section: this.section,
        fullTextSearch: this.fullTextSearch,
        searchableAs: this.searchableAs,
        titleColumn: this.titleColumn,
        subTitleColumn: this.subTitleColumn,
        indexes: this.columns.map((col) => col.index),
        columns: this.columns.filter((col) => col.visible || col.relatedModel).map((col) => ({
          id: col.id,
          index: col.index,
          name: col.name,
          label: col.label,
          type: col.type,
          visible: col.visible,
          layout: col.layout,
          searchable: col.searchable,
          sortable: col.sortable,
          filterable: col.filterable,
          vertical: col.vertical,
          required: col.required,
          attributes: col.attributes,
          relatedModel: col.relatedModel,
        })),
      };
    },
  },
  watch: {
    persistable: {
      handler() {
        this.persist();
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;
    await this.syncRelations();
    await this.syncHasManyRelations();
    await this.init();
    this.loading = false;
  },
  methods: {
    async init() {
      this.icons = Object.keys(orchidIcons).map((i) => ({ name: i }));

      if (!this.section && this.sections && this.sections.length) {
        this.section = this.sections[0].name;
      }

      this.loading = true;
      let module = await this.getModule();
      this.loading = false;

      if (module) {
        this.columns = module.columns;
      } else {
        module = {};
      }

      this.showInNav = module.showInNav !== undefined ? module.showInNav : true;
      this.navIcon = module.navIcon || 'doc';
      this.section = module.section || this.section;
      this.fullTextSearch = module.fullTextSearch !== undefined ? module.fullTextSearch : true;
      this.searchableAs = module.searchableAs || `${this.str.snakeCase(this.str.pluralize(this.model.tableName))}_index`;
      this.titleColumn = module.titleColumn || this.titleColumn;
      this.subTitleColumn = module.subTitleColumn || this.subTitleColumn;

      const persistedColumns = this.columns || [];

      const modelColumns = this.model.columns;

      const underscoredColumnIndex = modelColumns.findIndex((c) => c.name.indexOf('_') > -1);

      let hasManyRelationColumns = this.hasManyRelations.filter(
        (r) => persistedColumns.find((c) => c.id !== r.id),
      );

      if (!hasManyRelationColumns.length && !persistedColumns.length) {
        hasManyRelationColumns = this.hasManyRelations;
      }

      if (hasManyRelationColumns.length || !persistedColumns.length) {
        modelColumns.splice(
          underscoredColumnIndex,
          0,
          ...hasManyRelationColumns
            .filter((c) => !modelColumns.find((mc) => mc.id === c.id))
            .map((r) => ({
              id: r.id,
              name: this.str.snakeCase(this.str.pluralize(r.related.modelName)),
              relatedModel: r.related,
              type: 'hasManyRelation',
              visible: true,
            })),
        );
      }

      const mappable = [];

      modelColumns.forEach((col) => {
        const persisted = persistedColumns.find((c) => c.id === col.id);
        if (persisted) {
          persisted.attributes = col.attributes;
        }
        mappable.push(persisted || col);
      });

      const indexes = module.indexes || [];

      const columns = mappable.map((col, i) => ({
        ...col,
        index: col.index || (indexes.length === mappable.length ? indexes[i] : i),
        visible: col.visible !== undefined ? col.visible : this.getDefaultVisibility(col),
        label: lodash.startCase(col.name.replace(/[_-]/, ' ')),
        searchable: col.searchable !== undefined ? col.searchable : true,
        sortable: col.sortable !== undefined ? col.sortable : true,
        filterable: col.filterable !== undefined ? col.filterable : true,
        vertical: col.vertical !== undefined ? col.vertical : true,
        required: col.required !== undefined ? col.required : true,
        relatedModel: col.relatedModel,
        layout: col.layout ? {
          control: col.layout.control || this.getControlType(col),
          controlProps: col.layout.controlProps || { title: `${this.str.humanize(col.name)}` },
          listControlProps: col.layout.listControlProps || { copy: true },
          moduleVisibility: col.layout.moduleVisibility || ['listing', 'creating'],
        } : {
          control: this.getControlType(col),
          controlProps: { title: `${this.str.humanize(col.name)}` },
          listControlProps: { copy: true },
          moduleVisibility: ['listing', 'creating'],
        },
        inputControlComponent: this.getControl(col),
      }));

      this.selectedColumns = columns.filter((col) => col.visible).map((col) => col.name);

      this.sortedColumns = JSON.parse(JSON.stringify(columns));

      this.sortedColumns.sort((a, b) => a.index - b.index);

      this.columns = columns;

      this.$nextTick(() => {
        this.ready = true;
        this.initialized = true;
      });
    },

    async syncRelations() {
      const { data } = await this.mutation({
        path: `database/relations/${this.model.id}`,
        like: true,
        refresh: true,
      });
      this.relations = this.getPersistedMutationValue(data);
    },

    async syncHasManyRelations() {
      this.hasManyRelations = (this.eloquentRelations || []).filter((r) => r.type === 'hasMany' && r.source.id === this.model.id)
        .map((r) => ({ ...r, related: this.blueprints.find((s) => s.id === r.related.id) }));
    },

    getControlType(column) {
      const type = column.layout && column.layout.control ? column.layout.control : null;

      return type || this.getInferredControlType(column);
    },

    getInferredControlType(column) {
      if (!column || !column.type) {
        return 'input';
      }

      if (column.type === 'hasManyRelation') {
        return 'hasManyRelation';
      }
      if (column.type === 'string') {
        return 'input';
      }
      if (column.type === 'text') {
        return 'htmlEditor';
      }
      if (column.type.indexOf('date') > -1 || column.type.indexOf('time') > -1) {
        return 'datetime';
      }
      if (column.type.indexOf('bool') > -1) {
        return 'boolean';
      }
      if (column.name.indexOf('_id') > -1 && column.attributes.us && !column.attributes.ai) {
        return 'relation';
      }

      return 'input';
    },

    getControl(column) {
      const type = this.getControlType(column);

      if (type === 'input') {
        return OrchidInput;
      }

      if (type === 'htmlEditor') {
        return OrchidQuillEditor;
      }

      if (type === 'textarea') {
        return OrchidTextArea;
      }

      if (type === 'datetime') {
        return OrchidDateTime;
      }

      if (type === 'select') {
        return OrchidSelect;
      }

      if (type === 'relation') {
        return OrchidRelation;
      }

      if (type === 'hasManyRelation') {
        return OrchidHasManyRelation;
      }

      if (type === 'checkbox') {
        return OrchidCheckbox;
      }

      if (type === 'boolean') {
        return OrchidBoolean;
      }

      return OrchidInput;
    },

    async getModule() {
      const { data } = await this.mutation({ path: `ui/admin/modules/${this.model.id}` });
      return data.value;
    },

    getDefaultVisibility(column) {
      if (!column.name || column.name.trim() === '') {
        return false;
      }

      const name = column.name.toLowerCase().trim();
      const { type } = column;

      return (type === 'string' || type === 'boolean' || type === 'text' || type === 'integer' || type === 'hasManyRelation' || type === 'relation')
                && name.indexOf('password') < 0;
    },

    persist() {
      if (!this.ready) {
        return;
      }

      this.$nextTick(() => {
        const payload = {
          name: 'Admin Panel Module',
          path: `ui/admin/modules/${this.model.id}`,
          value: this.persistable,
        };

        this.mutate(payload);
      });
    },

    onControlPropsUpdated(updatedProps, column) {
      const requiredProps = { title: column.layout.controlProps.title };
      column.layout.controlProps = { ...requiredProps, ...updatedProps };
    },

    onControlListPropsUpdated(updatedProps, column) {
      const requiredProps = { copy: column.layout.listControlProps.copy };
      column.layout.listControlProps = { ...requiredProps, ...updatedProps };
    },

    onModuleVisibilityUpdated(visibility, column) {
      column.layout.moduleVisibility = visibility;
    },

    onColumnOrderChanged(e) {
      this.$nextTick(() => {
        if (e && e.moved && e.moved.newIndex !== e.moved.oldIndex && e.moved.element) {
          let index = 0;
          // eslint-disable-next-line no-restricted-syntax
          for (const col of this.sortedColumns) {
            const colIndex = this.columns.findIndex((c) => c.id === col.id);
            if (colIndex > -1) {
              this.columns[colIndex].index = index;
            } else {
              this.columns[index].index = index;
            }
            index += 1;
          }
        }
      });
    },

    onModifyColumnDisplayOrder() {
      if (this.project && this.project.downloaded) {
        return;
      }

      this.reorderColumns = !this.reorderColumns;
    },
  },
};
</script>

<style scoped>

</style>
