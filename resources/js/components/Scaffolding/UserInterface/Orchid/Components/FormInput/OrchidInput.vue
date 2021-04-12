<template>
  <row>
    <column size="6">
      <form-input-title :centered="false" small title="Input Sub-Type" />
      <simple-select filterable
                     full-width
                     :entities="subTypes"
                     v-model="subType">
        <template slot-scope="{ entity }">
          <el-option :key="entity.name"
                     :label="entity.label"
                     :value="entity.name" />
        </template>
      </simple-select>
    </column>
    <column size="6">
      <form-input-title :centered="false" small>
        Mask
        <a href="https://github.com/RobinHerbots/Inputmask#options"
                target="_blank"
                class="m-l-5 small link">
                  <i class="fa fa-external-link"></i>
                  Reference
        </a>
      </form-input-title>
      <pg-input :disabled="!subTypeSupportsMask(subType)" class="input-max-height" v-model="inputMask" />
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgInput from '@/components/Forms/PgInput';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import SimpleSelect from '@/components/Select/SimpleSelect';

export default {
  name: 'OrchidInput',
  props: {
    tableName: String,
    column: {},
    persisted: {},
  },
  components: {
    SimpleSelect,
    FormInputTitle,
    PgInput,
    Row,
    Column,
  },
  data() {
    const persisted = this.persisted || {};

    return {
      inputMask: persisted.mask || '',

      subType: persisted.subType || 'text',
      subTypes: [
        {
          name: 'text',
          label: 'Text',
        },
        {
          name: 'email',
          label: 'Email',
        },
        {
          name: 'password',
          label: 'Password',
        },
        {
          name: 'number',
          label: 'Number',
        },
        {
          name: 'file',
          label: 'File',
        },
        {
          name: 'hidden',
          label: 'Hidden',
        },
        {
          name: 'color',
          label: 'Color',
        },
        {
          name: 'range',
          label: 'Range',
        },
        {
          name: 'url',
          label: 'URL',
        },
      ],
    };
  },
  created() {
    // TODO: Have the parent set the defaults to avoid the following comparison...
    const persisted = JSON.stringify(this.persisted || {});
    const broadcastable = JSON.stringify(this.broadcastable);

    if (persisted !== broadcastable) {
      this.broadcastChanges(this.broadcastable);
    }
  },
  computed: {
    broadcastable() {
      return {
        mask: this.inputMask,
        subType: this.subType,
      };
    },
  },
  watch: {
    broadcastable: {
      handler(v) {
        this.broadcastChanges(v);
      },
    },
  },
  methods: {
    broadcastChanges(changes) {
      this.$emit('updated', changes);
    },

    subTypeSupportsMask(subType) {
      return subType === 'text' || subType === 'email' || subType === 'password' || subType === 'number' || subType === 'url';
    },
  },
};
</script>

<style scoped>

</style>
