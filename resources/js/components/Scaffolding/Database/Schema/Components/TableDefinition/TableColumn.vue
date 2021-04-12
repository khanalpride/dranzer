<template>
  <row>
    <column>
      <form-input-group no-margin>
        <simple-button :disabled="disabled"
                       color-class="default"
                       class="column-type-selector-button"
                       @click="$emit('clone')"
                       v-if="!disableCloning">
          <i class="fa fa-clone" />
        </simple-button>
        <pg-input
          :ref="column.id"
          :disabled="disabled"
          validate
          :validation-result="isColumnNameValid(columnName)"
          v-model="columnName"
          placeholder="Column Name"
          class="column-text-input"
          @keyup.native.13="onColumnNameEnterKeyPressed"
          @keydown.native="onColumnNameKeyDown"
        />
        <toggle-button class="column-type-selector-button"
                       text="S"
                       off-color-class="default"
                       content="Toggle string"
                       :tooltip-delay="200"
                       :value="column.type === 'string'"
                       :disabled="disabled"
                       :class="{'active-column': column.type === 'string'}"
                       @change="onChangeColumnTypeFromPreset($event, 'string')"
                       v-if="!hideTypeAliases" />
        <toggle-button class="column-type-selector-button"
                       text="T"
                       off-color-class="default"
                       content="Toggle text"
                       :value="column.type === 'text'"
                       :disabled="disabled"
                       :class="{'active-column': column.type === 'text'}"
                       @change="onChangeColumnTypeFromPreset($event, 'text')"
                       v-if="!hideTypeAliases" />
        <toggle-button class="column-type-selector-button"
                       text="D"
                       off-color-class="default"
                       :value="column.type === 'double'"
                       content="Toggle double"
                       :disabled="disabled"
                       :class="{'active-column': column.type === 'double'}"
                       @change="onChangeColumnTypeFromPreset($event, 'double')"
                       v-if="!hideTypeAliases" />
        <toggle-button class="column-type-selector-button"
                       text="I"
                       off-color-class="default"
                       :value="column.type === 'integer'"
                       content="Toggle integer"
                       :disabled="disabled"
                       :class="{'active-column': column.type === 'integer'}"
                       @change="onChangeColumnTypeFromPreset($event, 'integer')"
                       v-if="!hideTypeAliases" />
        <toggle-button class="column-type-selector-button"
                       text="B"
                       off-color-class="default"
                       :value="column.type === 'boolean'"
                       content="Toggle boolean"
                       :disabled="disabled"
                       :class="{'active-column': column.type === 'boolean'}"
                       @change="onChangeColumnTypeFromPreset($event, 'boolean')"
                       v-if="!hideTypeAliases" />
        <el-select
          :disabled="disabled"
          :value="column.type"
          filterable
          class="column-type-selector"
          @change="onColumnTypeChanged($event)"
        >
          <el-option
            v-for="type in columnTypes"
            :key="type.name"
            :value="type.name"
            :label="type.text"
          />
        </el-select>

        <pg-input
          :disabled="disabled|| !supportsLength"
          :value="columnLength"
          style="width: 30px !important;"
          placeholder="Length"
          content="Column Length"
          @input="onColumnLengthUpdated"
        />

        <toggle-button
          class="column-attr-button"
          on-color-class="green"
          off-color-class="default"
          :text="attr.text"
          :value="attr.value"
          :tooltip-delay="200"
          :content="attr.content"
          :disabled="broadcastingDisabled || disabled || attr.disabled"
          :key="attr.id"
          @change="onAttrToggled($event, attr.id)"
          v-for="attr in attrs"
        />

        <simple-button color-class="danger" :disabled="disabled" @click="onDeleteColumn">
          <i class="fa fa-close"/>
        </simple-button>
      </form-input-group>
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgInput from '@/components/Forms/PgInput';
import ToggleButton from '@/components/Forms/Buttons/ToggleButton';
import columnTypes from '@/data/scaffolding/database/blueprint/column_types';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import ValidationHelpers from '@/helpers/validation_helpers';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';

export default {
  name: 'TableColumn',
  props: {
    column: {},
    options: {},
    disabled: Boolean,
    locked: Boolean,
    disableCloning: Boolean,
    hideTypeAliases: Boolean,
    disableAutoIncrementing: Boolean,
    forceLowercase: Boolean,
    autoIncrementingColumn: {
      type: Object,
      default: null,
    },
  },
  components: {
    SimpleButton,
    FormInputGroup,
    ToggleButton,
    PgInput,
    Column,
    Row,
  },
  data() {
    return {
      broadcastingDisabled: false,

      columnTypes: [],

      previousType: null,
    };
  },
  computed: {
    lowercaseName() {
      return this.forceLowercase || this.options.forceLowercase;
    },

    attrs() {
      return [
        {
          id: 'ai',
          text: 'AI',
          content: 'Toggle Auto Increment',
          value: this.column.attributes.ai,
          disabled: this.disableAutoIncrementing || !this.canAutoIncrement,
        },
        {
          id: 'us',
          text: 'US',
          content: 'Toggle Unsigned',
          value: this.isAutoIncrementing
            || (this.supportsUnsigned && this.column.attributes.us),
          disabled: this.isAutoIncrementing,
        },
        {
          id: 'n',
          text: 'N',
          content: 'Toggle Nullable',
          value: !this.isAutoIncrementing && this.column.attributes.n,
          disabled: this.isAutoIncrementing || !this.canBeNullable,
        },
        {
          id: 'u',
          text: 'U',
          content: 'Toggle Unique',
          value: this.column.attributes.u,
          disabled: !this.canBeUnique,
        },
        {
          id: 'f',
          text: 'F',
          content: 'Toggle Fillable',
          value: this.options.createModel
            && (!this.options.unguarded && this.column.attributes.f && !this.column.attributes.ug),
          disabled: !this.options.createModel
            || this.options.unguarded
            || !this.canBeFillable,
        },
        {
          id: 'ug',
          text: 'UG',
          content: 'Toggle Unguarded',
          value: this.options.createModel
            && (
              this.options.unguarded
                ? !this.isAutoIncrementing
                : (this.column.attributes.ug && !this.column.attributes.f)
            ),
          disabled: !this.options.createModel
            || this.options.unguarded
            || !this.canBeUnguarded,
        },
        {
          id: 'h',
          text: 'H',
          content: 'Toggle Hidden',
          value: this.options.createModel && this.column.attributes.h,
          disabled: !this.options.createModel || !this.canBeHidden,
        },
      ];
    },

    isAutoIncrementing() {
      return this.column.attributes.ai && this.isUnsigned;
    },

    isUnsigned() {
      return this.column.attributes.us && this.supportsUnsigned;
    },

    supportsLength() {
      const { column } = this;

      const columnType = column.type;
      const lowerCasedColumnType = columnType.toLowerCase();

      return (
        columnType
        && (lowerCasedColumnType.indexOf('string') > -1
          || lowerCasedColumnType.indexOf('integer') > -1
          || lowerCasedColumnType.indexOf('double') > -1
          || lowerCasedColumnType.indexOf('float') > -1
          || lowerCasedColumnType.indexOf('decimal') > -1)
      );
    },

    canAutoIncrement() {
      const { column } = this;
      return column.type.toLowerCase().indexOf('integer') > -1
        || column.type.toLowerCase().indexOf('increment') > -1;
    },

    canBeNullable() {
      return true;
    },

    canBeUnique() {
      const { column } = this;

      const colType = column.type;

      const isSupportedType = colType === 'bigIncrements'
        || colType === 'bigInteger'
        || colType === 'char'
        || colType === 'date'
        || colType === 'dateTime'
        || colType === 'dateTimeTz'
        || colType === 'decimal'
        || colType === 'double'
        || colType === 'enum'
        || colType === 'float'
        || colType === 'increments'
        || colType === 'integer'
        || colType === 'ipAddress'
        || colType === 'json'
        || colType === 'jsonb'
        || colType === 'lineString'
        || colType === 'longText'
        || colType === 'macAddress'
        || colType === 'mediumIncrements'
        || colType === 'mediumInteger'
        || colType === 'mediumText'
        || colType === 'morphs'
        || colType === 'uuidMorphs'
        || colType === 'multiLineString'
        || colType === 'multiPoint'
        || colType === 'multiPolygon'
        || colType === 'nullableMorphs'
        || colType === 'nullableUuidMorphs'
        || colType === 'point'
        || colType === 'polygon'
        || colType === 'smallIncrements'
        || colType === 'smallInteger'
        || colType === 'string'
        || colType === 'text'
        || colType === 'time'
        || colType === 'timeTz'
        || colType === 'timestamp'
        || colType === 'timestampTz'
        || colType === 'tinyIncrements'
        || colType === 'tinyInteger'
        || colType === 'unsignedBigInteger'
        || colType === 'unsignedDecimal'
        || colType === 'unsignedInteger'
        || colType === 'unsignedMediumInteger'
        || colType === 'unsignedSmallInteger'
        || colType === 'unsignedTinyInteger'
        || colType === 'uuid'
        || colType === 'year';

      const fkCompatibleTypes = [
        'unsignedBigInteger',
        'unsignedMediumInteger',
        'unsignedSmallInteger',
        'unsignedTinyInteger',
      ];

      const referencesFK = fkCompatibleTypes.includes(column.type)
        && column.name.substr(column.name.length - 3) === '_id';

      return (
        isSupportedType
        && (!column.attributes.us || !column.attributes.ai)
        && !referencesFK
      );
    },

    canBeFillable() {
      return true;
    },

    canBeUnguarded() {
      return true;
    },

    canBeHidden() {
      return true;
    },

    supportsUnsigned() {
      const { column } = this;

      const columnType = column.type;

      return (
        columnType
        && (columnType.toLowerCase().indexOf('integer') > -1
          || columnType.toLowerCase().indexOf('increment') > -1)
      );
    },

    columnName: {
      get() {
        return this.column.name;
      },
      set(value) {
        const newName = this.fixColumnName(value);
        this.$emit('name-updated', { oldName: value, newName });
      },
    },

    columnLength() {
      return this.supportsLength ? this.column.attributes.length : null;
    },
  },
  async created() {
    this.columnTypes = columnTypes;
  },
  methods: {
    focusColumnNameInput() {
      this.$nextTick(() => {
        if (this.$refs[this.column.id]) {
          this.$refs[this.column.id].focus();
        }
      });
    },

    fixColumnName(name) {
      let newName = '';

      for (let i = 0; i < name.length; i += 1) {
        const code = name.charCodeAt(i);
        if (
          (code >= 65 && code <= 90)
          || (code >= 97 && code <= 122)
          || (code >= 48 && code <= 57)
          || (
            code === 189 || code === 58 || code === 32
            || code === 33 || code === 57 || code === 40
            || code === 41 || code === 48 || code === 95
            || code === 32 || code === 45
          )
        ) {
          newName += name.charAt(i);
        }
      }

      newName = newName.replace(/[\s-]/ig, '_');

      if (this.lowercaseName) {
        newName = newName.toLowerCase();
      }

      return this.str.ellipse(newName, 42);
    },

    isColumnNameValid(columnName) {
      return { tipPlacement: 'left', ...ValidationHelpers.isValidTableColumnName(columnName, this.lowercaseName) };
    },

    onAttrToggled(checked, attr) {
      if (this.broadcastingDisabled) {
        return;
      }

      this.broadcastingDisabled = true;
      this.$emit('attr-updated', { checked, attr });
      this.$nextTick(() => {
        this.broadcastingDisabled = false;
      });
    },

    onChangeColumnTypeFromPreset(checked, targetType) {
      if (this.broadcastingDisabled || this.locked) {
        return;
      }

      let newType = targetType;

      if (checked) {
        this.previousType = this.column.type;
      } else if (this.previousType) {
        newType = this.previousType;
      }

      this.onColumnTypeChanged(newType, 'btn');

      this.$nextTick(() => {
        this.broadcastingDisabled = false;
      });
    },

    onColumnTypeChanged(newType) {
      if (this.broadcastingDisabled) {
        return;
      }

      this.broadcastingDisabled = true;

      this.$emit('type-updated', newType);

      this.$nextTick(() => {
        this.broadcastingDisabled = false;
      });
    },

    onColumnLengthUpdated(newLength) {
      this.$emit('length-updated', newLength);
    },

    onDeleteColumn() {
      this.$emit('delete');
    },

    onColumnNameEnterKeyPressed() {
      this.$emit('new');
    },

    onColumnNameKeyDown(e) {
      const code = e.keyCode;

      if (e.ctrlKey || e.altKey) {
        return;
      }

      if (
        code === 8
        || code === 9
        || (code >= 37 && code <= 40)
      ) {
        return;
      }

      if (e.shiftKey && code !== 189 && code !== 186 && code !== 49 && code !== 57 && code !== 48 && this.lowercaseName) {
        e.preventDefault();
      }

      if (this.columnName && this.columnName.length >= 42) {
        e.preventDefault();
      }

      if (
        (code >= 65 && code <= 90)
        || (code >= 97 && code <= 122)
        || (code >= 48 && code <= 57)
        || (code === 189 || code === 186 || code === 32 || code === 95 || code === 45)
      ) {
        return;
      }

      e.preventDefault();
    },
  },
};
</script>

<style>
div.el-select.column-type-selector > div > input {
  border: solid 1px #f1f2f5 !important;
}
</style>

<!--suppress CssUnusedSymbol -->
<style scoped>
.column-type-selector {
  width: 210px !important;
}

.column-text-input {
  height: 40px !important;
  width: 100px !important;
  border: solid 1px #f1f2f5;
}

.column-attr-button {
  height: 40px !important;
}

.column-type-selector-button {
  height: 40px !important;
  border: solid 1px #f1f2f5 !important;
}

.column-type-selector-button.active-column {
  border-top: solid 1px #02a902 !important;
}
</style>
