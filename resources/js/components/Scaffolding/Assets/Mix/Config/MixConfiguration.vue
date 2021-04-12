<template>
    <content-card heading="Mix Configuration">
        <div v-if="fetchingMutations || loading">
            <row>
                <column size="4" offset="4">
                    <p class="text-center">Restoring Configuration...</p>
                    <indeterminate-progress-bar />
                </column>
            </row>
        </div>

        <div v-else>
            <row>
                <column centered>
                    <pg-check-box v-model="version"
                                  centered
                                  label="Version Assets In Production"
                                  @change="persist"/>

                    <pg-check-box v-model="disableSuccessNotifications"
                                  centered
                                  label="Disable Success Notifications"
                                  @change="persist"/>
                </column>
            </row>
        </div>
    </content-card>
</template>

<script>
import mutations from '@/mixins/mutations';
import Row from '@/components/Layout/Grid/Row';
import asyncImports from '@/mixins/async_imports';
import Column from '@/components/Layout/Grid/Column';
import ContentCard from '@/components/Cards/ContentCard';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';

export default {
  name: 'MixConfiguration',
  mixins: [asyncImports, mutations],
  components: {
    PgCheckBox, IndeterminateProgressBar, Column, ContentCard, Row,
  },
  data() {
    return {
      loading: false,

      version: true,
      disableSuccessNotifications: true,
    };
  },
  async created() {
    this.loading = true;
    const { data } = await this.mutation({ path: 'assets/mix/config' });
    this.loading = false;

    if (data.value) {
      this.version = data.value.version !== undefined ? data.value.version : true;
      this.disableSuccessNotifications = data.value.disableSuccessNotifications !== undefined ? data.value.disableSuccessNotifications : true;
    }
  },
  methods: {
    persist() {
      const name = 'Mix Configuration';
      const path = 'assets/mix/config';
      const value = { version: this.version, disableSuccessNotifications: this.disableSuccessNotifications };

      const payload = {
        name,
        path,
        value,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>
