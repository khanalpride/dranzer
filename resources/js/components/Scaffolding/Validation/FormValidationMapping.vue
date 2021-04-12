<template>
    <div>
        <row v-if="loading">
            <column size="4" offset="4">
                <p class="text-center">Restoring Rules...</p>
                <indeterminate-progress-bar />
            </column>
        </row>

        <row v-else>
            <column centered>
                <p class="text-primary no-margin bold">Rules</p>
            </column>

            <column>
                <separator/>
            </column>

            <column centered size="4" offset="4">
                <simple-select multiple filterable collapse-tags v-model="col.applied" :entities="col.rules" @change="toggleRules($event, col)">
                  <template slot-scope="{ entity }">
                    <el-option :label="entity.name" :value="entity.name" :key="entity.name" />
                  </template>
                </simple-select>
            </column>

            <column v-if="getCompiled(col) !== ''">
                <separator/>
            </column>

            <column centered>
                <p class="text-green no-margin" v-html="getCompiled(col)"></p>
            </column>

            <column v-if="getActiveRulesAcceptingInput(col).length">
                <row>
                    <column>
                        <separator/>
                    </column>

                    <column :key="r.name" v-for="(r, i) in getActiveRulesAcceptingInput(col)">
                        <row>
                            <column v-if="i > 0">
                                <separator/>
                            </column>
                            <column>
                                <row>
                                  <column :size="r.input2 !== undefined ? 4 : 12"
                                          :offset="r.input2 !== undefined ? 2 : 0" :centered="!r.input2 !== undefined">
                                  <p class="text-primary" v-html="r.input1Desc"></p>
                                    </column>

                                    <column size="4" v-if="r.input2 !== undefined" centered>
                                        <p class="text-primary" v-html="r.input2Desc"></p>
                                    </column>
                                </row>
                            </column>
                            <column>
                                <row>
                                    <column size="4" :offset="r.input2 !== undefined ? 2 : 4" v-if="r.input1 !== undefined">
                                        <pg-input v-model="r.input1" @input="ruleInputChanged" />
                                    </column>
                                    <column size="4" v-if="r.input2 !== undefined">
                                        <pg-input v-model="r.input2" @input="ruleInputChanged" />
                                    </column>
                                </row>
                            </column>
                        </row>
                    </column>
                </row>
            </column>
        </row>
    </div>
</template>

<script>
import SimpleSelect from '@/components/Select/SimpleSelect';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import PgInput from '@/components/Forms/PgInput';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'FormValidationMapping',
  mixins: [asyncImports, mutations],
  components: {
    SimpleSelect,
    IndeterminateProgressBar,
    PgInput,
    Separator,
    Column,
    Row,
  },
  props: {
    model: {},
    column: {},
  },
  data() {
    return {
      loading: false,

      col: this.column,

      compiled: '',

      compiling: false,
    };
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: `validation/mapping/${this.model.id}/${this.col.name}` });
      this.col = data.value || this.column;
    },

    persist() {
      const name = 'Form Validation Mapping';
      const path = `validation/mapping/${this.model.id}/${this.col.name}`;
      const value = {
        ...this.col, modelId: this.model.id, compiled: this.getRawCompiled(this.col, false),
      };

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },

    activeRuleToString() {
      return 'active';
    },

    activeUrlRuleToString() {
      return 'active_url';
    },

    afterRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'after', highlight);
    },

    afterOrEqualRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'after_or_equal', highlight);
    },

    alphaRuleToString() {
      return 'alpha';
    },

    alphaDashRuleToString() {
      return 'alpha_dash';
    },

    arrayRuleToString() {
      return 'array';
    },

    bailRuleToString() {
      return 'bail';
    },

    beforeRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'before', highlight);
    },

    beforeOrEqualRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'before_or_equal', highlight);
    },

    betweenRuleToString(input1, input2, highlight = true) {
      return this.doubleInputRuleToString(input1, input2, 'between', highlight);
    },

    booleanRuleToString() {
      return 'boolean';
    },

    confirmedRuleToString() {
      return 'confirmed';
    },

    dateRuleToString() {
      return 'date';
    },

    dateEqualsRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'date_equals', highlight);
    },

    dateFormatRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'date_format', highlight);
    },

    differentRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'different', highlight);
    },

    digitsRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'digits', highlight);
    },

    digitsBetweenRuleToString(input1, input2, highlight = true) {
      return this.doubleInputRuleToString(input1, input2, 'digits_between', highlight);
    },

    distinctRuleToString() {
      return 'date';
    },

    emailRuleToString() {
      return 'email';
    },

    startsWithRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'starts_with', highlight);
    },

    endsWithRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'ends_with', highlight);
    },

    excludeIfRuleToString(input1, input2, highlight = true) {
      return this.doubleInputRuleToString(input1, input2, 'exclude_if', highlight);
    },

    excludeUnlessRuleToString(input1, input2, highlight = true) {
      return this.doubleInputRuleToString(input1, input2, 'exclude_unless', highlight);
    },

    uniqueRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'unique', highlight);
    },

    existsRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'exists', highlight);
    },

    fileRuleToString() {
      return 'file';
    },

    filledRuleToString() {
      return 'filled';
    },

    gtRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'gt', highlight);
    },

    gteRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'gte', highlight);
    },

    ltRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'lt', highlight);
    },

    lteRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'lte', highlight);
    },

    inArrayRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'in_array', highlight);
    },

    imageRuleToString() {
      return 'image';
    },

    integerRuleToString() {
      return 'integer';
    },

    ipRuleToString() {
      return 'ip';
    },

    ipv4RuleToString() {
      return 'ipv4';
    },

    ipv6RuleToString() {
      return 'ipv6';
    },

    jsonRuleToString() {
      return 'json';
    },

    minRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'min', highlight);
    },

    maxRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'max', highlight);
    },

    mimetypesRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'mime_types', highlight);
    },

    mimesRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'mimes', highlight);
    },

    regexRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'regex', highlight);
    },

    notRegexRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'not_regex', highlight);
    },

    nullableRuleToString() {
      return 'nullable';
    },

    numericRuleToString() {
      return 'numeric';
    },

    passwordRuleToString() {
      return 'password';
    },

    presentRuleToString() {
      return 'present';
    },

    requiredRuleToString() {
      return 'required';
    },

    requiredIfRuleToString(input1, input2, highlight = true) {
      return this.doubleInputRuleToString(input1, input2, 'required_if', highlight);
    },

    requiredUnlessRuleToString(input1, input2, highlight = true) {
      return this.doubleInputRuleToString(input1, input2, 'required_unless', highlight);
    },

    requiredWithRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'required_with', highlight);
    },

    requiredWithAllRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'required_with_all', highlight);
    },

    requiredWithoutRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'required_without', highlight);
    },

    requiredWithoutAllRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'required_without_all', highlight);
    },

    sameRuleToString(input, highlight = true) {
      return this.singleInputRuleToString(input, 'same', highlight);
    },

    stringRuleToString() {
      return 'string';
    },

    timezoneRuleToString() {
      return 'timezone';
    },

    urlRuleToString() {
      return 'url';
    },

    uuidRuleToString() {
      return 'uuid';
    },
    /// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    singleInputRuleToString(input, ruleName, highlight = true) {
      if (!input || input.trim() === '') {
        return '';
      }

      return highlight
        ? `${ruleName}:<span class="text-info bold">${input}</span>`
        : `${ruleName}:${input}`;
    },

    doubleInputRuleToString(input1, input2, ruleName, highlight = true) {
      if (!input1 || input1.trim() === '') {
        return '';
      }

      if (!input2 || input2.trim() === '') {
        return '';
      }

      input1 = input1.trim();
      input2 = input2.trim();

      return highlight
        ? `${ruleName}:<span class="text-info bold">${input1}</span>,<span class="text-info bold">${input2}</span>`
        : `${ruleName}:${input1},${input2}`;
    },

    /// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    studlyRule(str) {
      if (!str || str.trim().length < 2 || str.indexOf('_') < 0) {
        return str;
      }

      const words = str.replace(/_/ig, ' ').split(' ');

      if (words.length === 1) {
        return words[0];
      }

      return words[0] + words.slice(1).map((w) => w.substr(0, 1).toUpperCase() + w.substr(1)).join('');
    },

    toggleRules(rules, column) {
      const included = [];
      column.rules.forEach((r) => {
        if (rules.includes(r.name)) {
          r.active = true;
          included.push(r.name);
        }
      });

      column.rules.forEach((r) => {
        if (!included.includes(r.name)) {
          r.active = false;
        }
      });

      this.$forceUpdate();
      this.persist();
    },

    compileRules(column, highlight = true) {
      const rules = column.rules.filter((r) => r.active);

      const compiled = [];

      rules.forEach((r) => {
        const ruleMethod = `${this.studlyRule(r.name)}RuleToString`;

        const ruleToStr = r.input2 !== undefined
          ? this[ruleMethod](r.input1, r.input2, highlight)
          : this[ruleMethod](r.input1, highlight);

        if (!ruleToStr || ruleToStr.trim() === '') {
          return false;
        }

        compiled.push(ruleToStr);
      });

      return compiled;
    },

    getCompiled(column, highlight = true) {
      return this.compileRules(column, highlight).join(' | ');
    },

    getRawCompiled(column, highlight = false) {
      return this.compileRules(column, highlight).join('|');
    },

    getActiveRulesAcceptingInput(column) {
      return column.rules.filter((c) => c.active && c.input1 !== undefined);
    },

    ruleInputChanged() {
      this.$forceUpdate();
      this.persist();
    },
  },
};
</script>

<style scoped>

</style>
