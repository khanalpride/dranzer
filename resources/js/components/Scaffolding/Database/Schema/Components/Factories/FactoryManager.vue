<template>
  <content-container col-size="10" col-offset="1">
    <row>
      <column centered>
        <pg-check-box centered
                      label="Create Factory"
                      v-model="createFactory"
                      @change="persist" />
        <pg-check-box centered
                      label="Create Seeder"
                      :value="createFactory && createSeeder"
                      :disabled="!createFactory"
                      @input="createSeeder = $event"
                      @change="persist" />
      </column>

      <column centered size="4" offset="4">
        <separator />
        <p class="text-info hint-text">Seed Count</p>
        <el-input-number :disabled="!createFactory || !createSeeder || (project && project.downloaded)"
                         v-model="seedCount" size="small" :min="1" :max="5000" @change="persist" />
      </column>
    </row>
  </content-container>
</template>

<script>
import ContentContainer from '@/components/Content/ContentContainer';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import mutations from '@/mixins/mutations';
import { mapState } from 'vuex';

export default {
  name: 'FactoryManager',
  props: {
    blueprint: {},
  },
  mixins: [mutations],
  components: {
    Separator,
    Row,
    Column,
    PgCheckBox,
    ContentContainer,
  },
  data() {
    return {
      createFactory: true,

      createSeeder: true,

      seedCount: 50,
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  async created() {
    await this.sync();
  },
  methods: {
    async sync() {
      const { data } = await this.mutation({ path: `database/factories/${this.blueprint.id}` });
      if (data.value) {
        this.createFactory = data.value.createFactory;
        this.createSeeder = data.value.createSeeder;
        this.seedCount = data.value.seedCount;
      }
    },

    persist() {
      if (!Number.isInteger(this.seedCount)) {
        return;
      }

      const payload = {
        name: 'Factory Settings',
        path: `database/factories/${this.blueprint.id}`,
        value: {
          createFactory: this.createFactory,
          createSeeder: this.createSeeder,
          seedCount: this.seedCount,
        },
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
