<template>
    <scaffolding-component-container heading="Configure JSON Response Presets" :loading="loading || fetchingMutations">
        <row push15 :key="preset.code" v-for="preset in presets" >
            <column size="2">
              <pg-input :ref="preset.id" @mouseleave.native="sortPresets" v-model="preset.code" validate
                        :validated="isPresetCodeValid(preset)"
                        validation-tooltip="Preset code must be an integer. e.g. 200" tooltip-placement="left"
                        @input="persist" placeholder="Code"/>
            </column>

            <column :size="preset.canDelete ? 9 : 10">
                <pg-input v-model="preset.message" @mouseleave.native="sortPresets" @input="persist" placeholder="Message" />
            </column>

            <column size="1" v-if="preset.canDelete">
                <button class="btn btn-danger pull-right" @click="deletePreset(preset)"><i class="fa fa-close"></i></button>
            </column>
        </row>

        <row push10>
            <column>
                <button class="btn btn-primary" @click="addPreset"><i class="fa fa-plus"></i></button>
            </column>
        </row>

    </scaffolding-component-container>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import PgInput from '@/components/Forms/PgInput';

export default {
  name: 'JsonPresets',
  mixins: [asyncImports, mutations],
  components: {
    PgInput, ScaffoldingComponentContainer, Column, Row,
  },
  data() {
    return {
      loading: false,

      presets: [
        {
          code: 200,
          message: 'Operation completed successfully.',
          removable: false,
        },
        {
          code: 201,
          message: 'Resource created successfully.',
          removable: false,
        },
        {
          code: 400,
          message: 'Bad input parameter. Make sure the data is provided in the requested format.',
          removable: false,
        },
        {
          code: 401,
          message: 'You have passed in an invalid Auth token and are unauthorized to perform the requested operation.'
              + ' Refresh the token and then try again.',
          removable: false,
        },
        {
          code: 403,
          message: 'You are not allowed to perform the request operation.',
          removable: false,
        },
        {
          code: 404,
          message: 'The requested resource was not found.',
          removable: false,
        },
        {
          code: 405,
          message: 'The resource does not support the specified HTTP verb.',
          removable: false,
        },
        {
          code: 409,
          message: 'The requested operation would result in a conflict due to a previous operation and can not be executed.',
          removable: false,
        },
        {
          code: 411,
          message: 'The Content-Length header is required.',
          removable: false,
        },
        {
          code: 429,
          message: 'The requested operation cannot be completed because we are receiving too many requests. Slow down and try again later.',
          removable: false,
        },
        {
          code: 500,
          message: 'Something went wrong and the requested operation was not completed successfully.',
          removable: false,
        },
        {
          code: 503,
          message: 'Service Unavailable.',
          removable: false,
        },
      ],
    };
  },
  async created() {
    this.loading = true;
    await this.sync();
    this.loading = false;
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: 'api/presets/response/json' });
      const persisted = data.value || [];

      const { presets } = this;

      // Restore the presets that didn't clear the validation during the last persistence call.
      presets.forEach((p) => {
        if (!persisted.find((pre) => pre.code.toString().trim() === p.code.toString().trim())) {
          persisted.push(p);
        }
      });

      this.presets = persisted;
      this.sortPresets();
    },

    sortPresets() {
      this.presets = this.presets.sort((a, b) => a.code - b.code);
    },

    persist() {
      const name = 'Json Response Presets';
      const path = 'api/presets/response/json';
      const value = this.presets.filter((p) => p.code.toString().trim() !== '' && p.message.trim() !== '' && this.isPresetCodeValid(p));

      this.mutate(value, name, path);
    },

    isPresetCodeValid(preset) {
      const code = preset.code.toString().trim();

      if (code === '') {
        return false;
      }

      return !Number.isNaN(Number(code));
    },

    addPreset() {
      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);

      // noinspection JSCheckFunctionSignatures
      this.presets.push({
        id,
        code: '',
        message: '',
        canDelete: true,
      });

      this.$nextTick(() => {
        if (this.$refs[id]) {
          this.$refs[id][0].focus();
        }
      });
    },

    deletePreset(preset) {
      const pIndex = this.presets.findIndex((p) => p.id === preset.id);
      if (pIndex > -1) {
        this.presets.splice(pIndex, 1);
        this.persist();
      }
    },
  },
};
</script>

<style scoped>

</style>
