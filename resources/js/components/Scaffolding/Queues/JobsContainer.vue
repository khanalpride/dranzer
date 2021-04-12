<template>
    <scaffolding-component-container heading="Configure Jobs" :loading="loading || fetchingMutations">
        <row>
            <column>
              <pg-input v-model="definition"
                        placeholder="Comma separated job names..."
                        @keyup.enter.native="onCreateJobsFromInput" />
            </column>

          <column push10 v-if="jobs.length">
            <tabs-manager ref="jobTabs" :tabs="tabs" path="config/jobs/tabs/active" @remove="onDeleteJob">
              <template :slot="job.id" v-for="job in jobs">
                <job :key="job.id" :persisted="job" :blueprints="blueprints" :mailables="mailables" :notifications="notifications" />
              </template>
            </tabs-manager>
          </column>
        </row>
    </scaffolding-component-container>
</template>

<script>
import mutations from '@/mixins/mutations';
import Row from '@/components/Layout/Grid/Row';
import PgInput from '@/components/Forms/PgInput';
import asyncImports from '@/mixins/async_imports';
import Column from '@/components/Layout/Grid/Column';
import TabsManager from '@/components/Tabs/TabsManager';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import Job from '@/components/Scaffolding/Queues/Job';
import sharedMutations from '@/mixins/shared_mutations';

export default {
  name: 'JobsContainer',
  mixins: [asyncImports, mutations, sharedMutations],
  components: {
    Job,
    TabsManager,
    PgInput,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  data() {
    return {
      loading: false,

      definition: '',

      jobs: [],

      mailables: [],

      notifications: [],

      blueprints: [],
    };
  },
  computed: {
    tabs() {
      return this.jobs.map((j) => ({ id: j.id, name: j.name, removable: true }));
    },
  },
  async created() {
    this.loading = true;
    await this.syncJobs();
    await this.syncMailables();
    await this.syncNotifications();
    await this.assignBlueprints();
    this.loading = false;
  },
  methods: {
    async syncJobs() {
      const { data } = await this.mutation({ path: 'queues/jobs' });
      this.jobs = data.value || this.jobs;
    },

    async syncMailables() {
      const { data } = await this.mutation({ path: 'mail/mailables/', like: true, refresh: true });
      this.mailables = data.value ? data.value.map((v) => v.value) : this.mailables;
    },

    async syncNotifications() {
      const { data } = await this.mutation({ path: 'notifications/', like: true, refresh: true });
      this.notifications = data.value ? data.value.map((v) => v.value) : this.notifications;
    },

    persistJobs() {
      const payload = {
        name: 'Jobs',
        path: 'queues/jobs',
        value: this.jobs,
      };

      this.mutate(payload);
    },

    onCreateJobsFromInput() {
      const input = this.definition.trim();

      if (input === '') {
        return;
      }

      const jobs = input.split(',');

      jobs.forEach((jobName) => {
        if (!/^[A-Za-z][a-zA-Z0-9]+$/g.test(jobName)) {
          return false;
        }

        if (
          !this.jobs.find(
            (j) => j.name.toLowerCase().trim()
              === jobName.toLowerCase().trim(),
          )
        ) {
          const newJob = {
            id: `J${Math.round(Math.random() * Number.MAX_SAFE_INTEGER)}`,
            name: this.str.humanize(jobName),
          };

          this.jobs.push(newJob);
          this.persistJobs();
        }

        return true;
      });

      this.definition = '';
    },

    async onDeleteJob(jobId) {
      const jIndex = this.jobs.findIndex((j) => j.id === jobId);
      if (jIndex > -1) {
        const { status } = await this.deleteMutation(`queues/jobs/${jobId}`);
        if (status === 201 || status === 404) {
          this.jobs.splice(jIndex, 1);
          this.persistJobs();
          this.$nextTick(() => {
            if (this.$refs.jobTabs) {
              this.$refs.jobTabs.activateTabByIndex(this.jobs.length - 1);
            }
          });
        }
      }
    },
  },
};
</script>

<style scoped>

</style>
