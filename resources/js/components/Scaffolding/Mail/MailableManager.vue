<template>
  <scaffolding-component-container
    heading="Configure Mailables"
    :loading="loading || fetchingMutations"
  >
    <row>
      <column>
        <pg-input v-model="definition"
                  validate
                  :validated="isValidDefinition"
                  indicator-style="minimal"
                  placeholder="Comma separated mailable names..."
                  @keyup.native.enter="onCreateMailablesFromInput" />
      </column>

      <column>
        <row push10>
          <column :push15="index > 0" :key="mailable.id" v-for="(mailable, index) in mailables">
            <mailable :blueprints="blueprints" :persisted="mailable" @updated="onMailableUpdated($event, mailable)" @delete="onDeleteMailable(mailable)" />
          </column>
        </row>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import PgInput from '@/components/Forms/PgInput';
import Mailable from '@/components/Scaffolding/Mail/Mailable';
import sharedMutations from '@/mixins/shared_mutations';

export default {
  name: 'MailableManager',
  components: {
    Mailable,
    PgInput,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  mixins: [asyncImports, mutations, sharedMutations],
  data() {
    return {
      loading: false,

      definition: '',

      mailables: [],

      deleting: [],

      blueprints: [],
    };
  },
  computed: {
    isValidDefinition() {
      if (this.definition.trim() === '') {
        return true;
      }

      return /^[A-Z][a-zA-Z0-9,\s]+$/g.test(this.definition.trim());
    },
  },
  async created() {
    this.loading = true;
    await this.assignBlueprints();
    await this.syncMailables();
    this.loading = false;
  },
  methods: {
    async syncMailables() {
      const { data } = await this.mutation({
        path: 'mail/mailables/',
        like: true,
        refresh: true,
      });
      this.mailables = data.value
        ? data.value.map((v) => v.value)
        : this.mailables;
    },

    persistMailable(mailable) {
      const name = 'Mailable';
      const path = `mail/mailables/${mailable.id}`;

      const payload = {
        name,
        path,
        value: mailable,
      };

      this.mutate(payload);
    },

    onCreateMailablesFromInput() {
      const input = this.definition.trim();

      if (!input || input.trim() === '') {
        this.definition = '';
        return;
      }

      const mailables = input.split(',');

      mailables.forEach((mailable) => {
        const mailableName = mailable.replace(/\s/g, '').trim();

        if (!/^[A-Z][a-zA-Z0-9]+$/g.test(mailableName)) {
          return false;
        }

        if (
          !this.mailables.find(
            (m) => m.name.toLowerCase().trim()
              === mailableName.toLowerCase().trim(),
          )
        ) {
          const newMailable = {
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            name: mailableName,
            markdown: false,
            typeHint: [],
            testRoute: true,
            passAuthenticated: false,
            markdownMessage: {
              items: [{
                id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
                type: 'custom',
                value: '',
              }],
            },
          };

          this.mailables.push(newMailable);

          this.onMailableUpdated(newMailable, newMailable);
        }

        return true;
      });

      this.definition = '';
    },

    async onDeleteMailable(mailable) {
      const mIndex = this.mailables.findIndex((m) => m.id === mailable.id);
      if (mIndex > -1) {
        this.deleting.push(mailable.id);
        await this.deleteMutation(
          `mail/mailables/${mailable.id}`,
          { then: () => this.mailables.splice(mIndex, 1) },
        );
        this.deleting.splice(this.deleting.indexOf(mailable.id), 1);
      }
    },

    onMailableUpdated(updatedMailable, mailable) {
      const mailableIndex = this.mailables.findIndex((m) => m.id === mailable.id);

      if (mailableIndex > -1) {
        this.mailables[mailableIndex] = updatedMailable;
        this.persistMailable(this.mailables[mailableIndex]);
      }
    },
  },
};
</script>

<style scoped></style>
