<template>
  <div>
    <row v-if="loading">
      <column size="4" offset="4">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column centered>
        <pg-check-box v-model="config.disableAuthorization"
                      color-class="danger"
                      no-margin
                      centered
                      label="Disable Authorization" />
        <separator />
      </column>
      <column centered>
        <pg-check-box v-model="config.createResourceClass"
                      no-margin
                      centered
                      label="Create Resource Class" />
        <pg-check-box v-model="config.createCollectionClass"
                      no-margin
                      centered
                      label="Create Collection Class" />
        <separator />
      </column>
      <column v-if="config.createResourceClass">
        <row>
          <column size="2" offset="5">
            <form-input-title title="Resource Wrapper" />
            <pg-input validate
                      :validation-result="isWrapperValid"
                      v-model="config.wrapper" />
          </column>
        </row>
        <separator />
      </column>
      <column centered>
        <pg-check-box v-model="config.configCollectionDisplay"
                      no-margin
                      centered
                      label="Configure Collection Display Criteria" />
        <pg-check-box v-model="config.configResourceColumns"
                      no-margin
                      centered
                      label="Configure Resource Display Criteria" />
      </column>
      <column>
        <basic-content-section heading="Index Display Criteria"
                               prepend-separator
                               v-if="config.configCollectionDisplay">
          <row>
            <column size="2" offset="5">
              <form-input-title title="Pagination Limit" />
              <el-input-number :min="1"
                               :max="99999"
                               size="small"
                               v-model="config.displayCriteria.paginationLimit" />
            </column>
            <column size="4" offset="4">
              <separator />
              <form-input-title title="Display Columns" />
              <selectable-table-columns filterable clearable multiple collapse-tags
                                        :columns="model.columns" v-model="config.displayCriteria.columns" />
            </column>
          </row>
        </basic-content-section>
        <basic-content-section heading="Resource Display Criteria"
                               prepend-separator
                               v-if="config.configResourceColumns">
          <row>
            <column>
              <form-input-title title="Display Columns" />
              <selectable-table-columns filterable clearable multiple collapse-tags
                                        :columns="model.columns" v-model="config.resourceDisplayCriteria.columns" />
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
import FormInputTitle from '@/components/Typography/FormInputTitle';
import Separator from '@/components/Layout/Separator';
import PgInput from '@/components/Forms/PgInput';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import BasicContentSection from '@/components/Content/BasicContentSection';
import mutations from '@/mixins/mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import entity from '@/mixins/entity';
import SelectableTableColumns from '@/components/Select/SelectableTableColumns';
import ValidationHelpers from '@/helpers/validation_helpers';

export default {
  name: 'OrionApiResource',
  props: {
    model: {},
  },
  mixins: [mutations, entity],
  components: {
    SelectableTableColumns,
    IndeterminateProgressBar,
    BasicContentSection,
    PgCheckBox,
    PgInput,
    Separator,
    FormInputTitle,
    Row,
    Column,
  },
  data() {
    return {
      loading: false,

      config: {
        wrapper: 'data',
        createResourceClass: false,
        createCollectionClass: false,
        disableAuthorization: false,

        configCollectionDisplay: false,
        configCollectionColumns: false,
        configResourceColumns: false,

        displayCriteria: {
          columns: this.model.columns.map((c) => c.id),
          paginationLimit: 15,
        },

        resourceDisplayCriteria: {
          columns: this.model.columns.map((c) => c.id),
        },
      },
    };
  },
  computed: {
    isWrapperValid() {
      return ValidationHelpers.ensureAlphaNumericOnly(this.config.wrapper);
    },
  },
  watch: {
    config: {
      handler(v) {
        this.persist(v);
      },
      deep: true,
    },
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: `api/resources/${this.model.id}` });
      const persisted = this.getPersistedMutationValue(data);
      const config = this.syncEntity(persisted, this.config);

      config.displayCriteria.columns = config.displayCriteria.columns.filter(
        (c) => this.model.columns.find((col) => col.id === c),
      );

      this.config = config;

      if (!persisted) {
        this.persist();
      }
    },
    persist(update) {
      const payload = {
        name: 'API Resource',
        path: `api/resources/${this.model.id}`,
        value: update || this.config,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
