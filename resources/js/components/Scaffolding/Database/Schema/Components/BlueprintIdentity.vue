<template>
  <div>
    <row>
      <column size="4" offset="2">
        <pg-labeled-input
          ref="modelNameInput"
          label="Model Name"
          v-model="modelName"
          :disabled="cannotEdit || !options.createModel"
          :class="{ 'transparent-container': !options.createModel }"
          :validate="options.createModel"
          :validated="isModelNameValid"
          :validation-tooltip="modelNameValidationTooltip"
          @keydown.native="onModelInputKeyDown"
        />
      </column>

      <column size="4">
        <pg-labeled-input
          ref="tableNameInput"
          label="Table Name"
          v-model="tableName"
          :disabled="cannotEdit"
          validate
          :validated="isTableNameValid"
          :validation-tooltip="tableNameValidationTooltip"
          @keydown.native="onTableInputKeyDown"
        />
      </column>
    </row>
    <row>
      <column offset="2">
        <pg-check-box no-margin
                      label="Capitalize Words"
                      :value="cannotEdit ? true : options.createModel && options.capitalize"
                      :disabled="cannotEdit || !options.createModel"
                      @change="$emit('capitalize-toggled', $event)" />

        <inline-help-link
          content="Uses a very small dictionary for word recognition so don't expect it to capitalize all words.
          You can submit more words via pull requests." />
      </column>
    </row>
  </div>
</template>

<script>
import { snakeCase } from 'snake-case';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import basicEnDict from '@/data/dict/basic_en_dict';
import InlineHelpLink from '@/components/Help/InlineHelpLink';

export default {
  name: 'BlueprintIdentity',
  components: {
    InlineHelpLink,
    PgCheckBox,
    PgLabeledInput,
    Row,
    Column,
  },
  props: {
    persistedModelName: String,
    persistedTableName: String,
    options: {},
    cannotEdit: Boolean,
  },
  data() {
    return {
      spaceKey: false,
      capitalizeNext: false,
      locked: false,

      mutations: {
        modelName: {
          recognizedWords: [],
          capitalizeNextChar: false,
        },
      },

      maxModelNameLength: 42,
      maxTableNameLength: 42,
    };
  },
  computed: {
    isModelNameValid() {
      return !this.persistedModelName ? false : this.persistedModelName.trim() !== '';
    },

    isTableNameValid() {
      return !this.persistedTableName ? false : this.tableName.trim() !== '';
    },

    modelNameValidationTooltip() {
      if (this.persistedModelName === '') {
        return undefined;
      }

      return 'Invalid Model Name';
    },

    tableNameValidationTooltip() {
      if (this.persistedTableName === '') {
        return undefined;
      }

      return 'Invalid Table Name';
    },

    modelName: {
      get() {
        return this.persistedModelName;
      },

      set(value) {
        let alphaOnly = '';

        for (let i = 0; i < value.length; i += 1) {
          const code = value.charCodeAt(i);
          if ((code >= 65 && code <= 90) || (code >= 97 && code <= 122)) {
            alphaOnly += value.charAt(i);
          }
        }

        let transformed = alphaOnly.length === 1
          ? alphaOnly.toUpperCase()
          : alphaOnly.substr(0, 1).toUpperCase() + alphaOnly.substr(1);

        if (this.options.capitalize) {
          if ((this.mutations.modelName.capitalizeNextChar || this.capitalizeNext) && transformed.length) {
            transformed = transformed.substr(0, transformed.length - 1)
              + transformed.substr(transformed.length - 1).toUpperCase();
          }

          if (transformed === '') {
            this.mutations.modelName.recognizedWords = [];
          }

          let searchTerm = transformed;

          searchTerm = this.mutations.modelName.recognizedWords.reduce((acc, r) => acc.replace(r, ''), searchTerm);

          this.mutations.modelName.capitalizeNextChar = basicEnDict.indexOf(searchTerm.toLowerCase()) > -1;

          if (this.mutations.modelName.capitalizeNextChar) {
            this.mutations.modelName.recognizedWords.push(searchTerm);
          }
        }

        this.$emit('model-name-updated', { oldName: value, newName: transformed });
      },
    },

    tableName: {
      get() {
        return this.persistedTableName;
      },

      set(value) {
        const newName = this.getNewTableName(value);
        this.$emit('table-name-updated', { oldName: value, newName });
      },
    },
  },
  watch: {
    options: {
      handler(v) {
        if (!v.singularize) {
          this.locked = false;
        }
      },
      deep: true,
      immediate: true,
    },
  },
  methods: {
    focusModelNameInput() {
      this.$nextTick(() => {
        if (this.$refs.modelNameInput) {
          this.$refs.modelNameInput.focus();
        }
      });
    },

    getNewTableName(currentName) {
      let newName = '';

      for (let i = 0; i < currentName.length; i += 1) {
        const code = currentName.charCodeAt(i);
        if (
          (code >= 65 && code <= 90)
          || (code >= 97 && code <= 122)
          || (code >= 48 && code <= 57)
          || (code === 189 || code === 95 || code === 32 || code === 45)
        ) {
          newName += currentName.charAt(i);
        }
      }

      newName = snakeCase(newName);

      return newName.replace(/[\s-]/g, '_');
    },

    onModelInputKeyDown(e) {
      this.capitalizeNext = false;

      if (
        e.keyCode === 8
        || e.keyCode === 9
        || e.ctrlKey
        || (e.keyCode >= 37 && e.keyCode <= 40)
      ) {
        return;
      }

      if (this.modelName && (this.modelName.length >= Number(this.maxModelNameLength))) {
        e.preventDefault();
        return;
      }

      if (e.keyCode === 32) {
        this.spaceKey = true;
        e.preventDefault();
        return;
      }

      if (this.spaceKey && e.keyCode !== 32) {
        this.spaceKey = false;
        this.capitalizeNext = true;
      }

      const el = this.$refs.modelNameInput.element();
      const selectedText = el.value.substr(el.selectionStart, el.selectionEnd);

      if (selectedText && selectedText.trim() !== '') {
        return;
      }

      if (e.keyCode < 65 || e.keyCode > 90 || this.locked) {
        e.preventDefault();
      }
    },

    onTableInputKeyDown(e) {
      if (
        e.keyCode === 8
        || e.keyCode === 9
        || e.ctrlKey
        || (e.keyCode >= 37 && e.keyCode <= 40)
      ) {
        return;
      }

      if (this.tableName.length >= Number(this.maxTableNameLength)) {
        e.preventDefault();
      }
    },
  },
};
</script>

<style scoped>

</style>
