<template>
  <scaffolding-component-container
    :loading="loading || fetchingMutations"
    heading="Database Selector"
  >
    <row>
      <column centered push10>
        <radio-group
          v-model="selector"
          :options="selectors"
          tooltip-location="bottom"
          @input="onDBTypeChanged($event)"
        />
      </column>

      <column v-if="selector" push15>
        <content-card
          :heading="
            !moduleLoaded
              ? `Initializing ${getSelectorName(selector)}...`
              : heading
          "
        >
          <row v-if="!moduleLoaded">
            <column size="4" offset="4">
              <indeterminate-progress-bar />
            </column>
          </row>
          <row>
            <column>
              <component
                :is="selector"
                :key="selector"
                :heading="heading"
                @mounted="moduleLoaded = true"
              />
            </column>
          </row>
        </content-card>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import tooltips from '@/data/ux/tooltips';

import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';
import ContentCard from '@/components/Cards/ContentCard';
import RadioGroup from '@/components/Forms/Radio/RadioGroup';
import PendingImportProgress from '@/components/Progress/PendingImportProgress';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import PlainTextCallToAction from '@/components/Typography/CallToAction/PlainTextCallToAction';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';

const SQLiteConfiguration = () => import(
  /* webpackChunkName: "chunks/s-d-s-sqc" */ '@/components/Scaffolding/Database/Configurations/SQLiteConfiguration'
);
const MySQLConfiguration = () => import(
  /* webpackChunkName: "chunks/s-d-s-msc" */ '@/components/Scaffolding/Database/Configurations/MySQLConfiguration'
);
// const MongoDBConfiguration = () => import(
//   /* webpackChunkName: "chunks/s-d-s-mdc" */ '@/components/Scaffolding/Database/Configurations/MongoDBConfiguration'
// );
const PostgreSQLConfiguration = () => import(
  /* webpackChunkName: "chunks/s-d-s-pgc" */ '@/components/Scaffolding/Database/Configurations/PostgreSQLConfiguration'
);
const SQLServerConfiguration = () => import(
  /* webpackChunkName: "chunks/s-d-s-ssc" */ '@/components/Scaffolding/Database/Configurations/SQLServerConfiguration'
);

export default {
  name: 'DatabaseManager',
  components: {
    ScaffoldingComponentContainer,
    Separator,
    PendingImportProgress,
    IndeterminateProgressBar,
    ContentCard,
    RadioGroup,
    PlainTextCallToAction,
    Column,
    Row,
    SQLiteConfiguration,
    MySQLConfiguration,
    PostgreSQLConfiguration,
    SQLServerConfiguration,
  },
  mixins: [asyncImports, mutations],
  data() {
    return {
      loading: false,

      heading: null,

      selector: 'MySQLConfiguration',

      selectors: [
        {
          name: 'SQLite',
          key: 'SQLiteConfiguration',
          tooltip: tooltips.database.types.sqLite,
        },
        {
          name: 'MySQL',
          key: 'MySQLConfiguration',
          tooltip: tooltips.database.types.mySQL,
        },
        // {
        //   name: 'MongoDB',
        //   key: 'MongoDBConfiguration',
        //   tooltip: tooltips.database.types.mongoDB,
        // },
        {
          name: 'PostgreSQL',
          key: 'PostgreSQLConfiguration',
          tooltip: tooltips.database.types.postgreSQL,
        },
        {
          name: 'SQL Server',
          key: 'SQLServerConfiguration',
          tooltip: tooltips.database.types.sqlServer,
        },
      ],
    };
  },
  async created() {
    const databaseType = this.getPersistedMutationValue(await this.mutation({ path: 'database/type' }));

    const key = databaseType || this.selector.replace('Configuration', '');

    this.selector = `${key}Configuration`;

    this.onDBTypeChanged(key, false);
  },
  methods: {
    /**
     *
     * @param selector
     */
    getSelectorName(selector) {
      const sel = this.selectors.find((s) => s.key === selector);
      return sel ? sel.name : null;
    },

    /**
     *
     * @param selectorKey
     */
    setHeading(selectorKey) {
      this.heading = `${this.getSelectorName(
        `${selectorKey}Configuration`,
      )} Configuration`;
    },

    /**
     *
     * @param selectorKey
     * @param mutate
     */
    onDBTypeChanged(selectorKey, mutate = true) {
      this.moduleLoaded = false;
      this.addAsyncImport(selectorKey);
      this.setHeading(`${selectorKey.replace('Configuration', '')}`);

      if (mutate) {
        this.mutate(
          {
            name: 'Database Type',
            path: 'database/type',
            value: selectorKey.replace('Configuration', ''),
          },
        );
      }
    },
  },
};
</script>

<style scoped></style>
