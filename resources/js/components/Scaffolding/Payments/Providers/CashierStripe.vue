<template>
    <scaffolding-component-container heading="Configure Stripe" :loading="loading || fetchingMutations">
        <row>
            <column size="10" offset="1">
                <row>
                    <column centered size="4">
                      <form-input-title title="Currency" />
                        <el-select v-model="currency" placeholder="Select Currency" filterable @change="persistCurrency">
                            <el-option :value="currency" :key="currency" v-for="(desc, currency) in currencies">
                                <template slot="default">
                                    <span> {{ currency }}</span>
                                    <span style="float: right; margin-left: 40px;"> {{ desc }}</span>
                                </template>
                            </el-option>
                        </el-select>
                    </column>

                    <column centered size="4">
                      <form-input-title title="Currency Locale" />
                      <el-select v-model="locale" placeholder="Select Currency Locale" filterable @change="persistCurrencyLocale">
                          <el-option :value="locale.locale" :key="locale.locale" v-for="locale in currencyLocales">
                              <template slot="default">
                                  <span> {{ locale.locale }}</span>
                                  <span style="float: right; margin-left: 40px;"> {{ locale.country }}</span>
                              </template>
                          </el-option>
                      </el-select>
                    </column>

                    <column centered size="4">
                      <form-input-title title="Logger" />
                      <el-select v-model="logger" placeholder="Select Logger" filterable @change="persistLogger">
                            <el-option label="Stack" value="stack"></el-option>
                            <el-option label="Single" value="single"></el-option>
                            <el-option label="Daily" value="daily"></el-option>
                            <el-option label="Slack" value="slack"></el-option>
                            <el-option label="Syslog" value="syslog"></el-option>
                            <el-option label="ErrorLog" value="errorlog"></el-option>
                            <el-option label="Monolog" value="monolog"></el-option>
                      </el-select>
                    </column>
                </row>
            </column>

            <column>
                <separator />
            </column>

            <column>
              <tabs-manager :tabs="tabs" path="config/payments/stripe/tabs/active">
                <template slot="subscriptions">
                  <row>
                    <column>
                      <row push15 v-if="subscriptions.length">
                        <column :push10="index > 0" :key="s.id" v-for="(s, index) in subscriptions">
                          <content-card :heading="s.name || '*Unnamed Subscription*'" removable @delete="deleteSubscription(s)">
                            <row>
                              <column size="4" offset="4">
                                <pg-labeled-input v-model="s.name" label="Subscription Name" @input="persistSubscription(s)" />
                              </column>

                              <column>
                                <separator />
                              </column>

                              <column size="4" offset="4">
                                <form-input-title title="Subscription Plan(s)"
                                                  v-tooltip.10
                                                  content="You can find these in your stripe dashboard" />
                                <el-select class="el-sel-full-width"
                                           no-match-text="No Plans Found"
                                           no-data-text="No Plans"
                                           placeholder="Choose or add plans..."
                                           multiple
                                           allow-create
                                           filterable
                                           clearable
                                           collapse-tags
                                           v-model="s.plans"
                                           @change="persistSubscription(s)">
                                  <el-option :label="p"
                                             :value="p"
                                             :key="p"
                                             v-for="p in s.plans" />
                                </el-select>
                              </column>

                              <column push20 centered>
                                <pg-check-box v-model="s.acceptsCoupons"
                                              no-margin
                                              centered
                                              label="Accepts Coupon"
                                              @change="persistSubscription(s)" />
                                <pg-check-box v-model="s.payUpfront"
                                              no-margin
                                              centered
                                              label="Pay Upfront"
                                              @change="persistSubscription(s)" />
                              </column>

                              <column push20 centered v-if="s.features.length">
                                <row>
                                  <column size="10" offset="1">
                                    <content-card :heading="`Plan Features / Highlights (${s.features.length})`">
                                      <draggable v-model="s.features" @end="persistSubscription(s)">
                                        <row :key="f.id" v-for="f in s.features">
                                          <column>
                                            <form-input-group compact>
                                              <pg-input :ref="f.id"
                                                        v-model="f.desc"
                                                        placeholder="Feature Description"
                                                        @input="persistSubscription(s)"
                                                        @keyup.native.13="addSubscriptionFeature(s)" />
                                              <button class="btn btn-danger pull-right"
                                                      @click="deleteSubscriptionFeature(f, s)">
                                                <i class="fa fa-close"></i>
                                              </button>
                                            </form-input-group>
                                          </column>
                                        </row>
                                      </draggable>
                                    </content-card>
                                  </column>
                                </row>
                              </column>

                              <column size="10" offset="1" push5>
                                <separator v-if="!s.features.length" />
                                <row :push10="s.features.length > 0">
                                  <column :centered="!s.features.length">
                                    <button class="btn btn-primary"
                                            :disabled="s.features.length > 5"
                                            @click="addSubscriptionFeature(s)">
                                      <i class="fa fa-plus"></i>
                                      <span v-if="!s.features.length">Add Feature</span>
                                    </button>
                                  </column>
                                </row>
                              </column>

                            </row>
                          </content-card>

                        </column>
                      </row>

                      <row :push10="subscriptions.length > 0">
                        <column :centered="!subscriptions.length">
                          <button class="btn btn-primary" @click="addSubscription">
                            <i class="fa fa-plus"></i>
                            <span v-if="!subscriptions.length">Add Subscription</span>
                          </button>
                        </column>
                      </row>
                    </column>
                  </row>
                </template>
                <template slot="single-charges">
                  <row>
                    <column>
                      <pg-input v-model="singleChargeDefinition"
                                placeholder="Enter single charge name..."
                                @keyup.native.enter="onCreateSingleChargeFromInput" />
                    </column>
                    <column>
                      <row push15 v-if="singleCharges.length">
                        <column :push10="index > 0" :key="s.id" v-for="(s, index) in singleCharges">
                          <pg-check-box :value="1"
                                        :label="s.name || 'Unnamed Charge'"
                                        :disabled="isDeleting(s)"
                                        no-margin
                                        @change="onSingleChargeStateChanged($event, s)" />
                          <row push10>
                            <column size="2" class="m-l-30">
                              <pg-input v-model="s.amount"
                                        placeholder="Amount"
                                        :disabled="isDeleting(s)"
                                        @input="persistSingleCharge(s)" />
                            </column>
                          </row>
                        </column>
                      </row>
                    </column>
                  </row>
                </template>
              </tabs-manager>
            </column>
        </row>
    </scaffolding-component-container>
</template>

<script>
import Draggable from 'vuedraggable';
import Row from '@/components/Layout/Grid/Row';
import ContentCard from '@/components/Cards/ContentCard';
import Column from '@/components/Layout/Grid/Column';
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';
import Separator from '@/components/Layout/Separator';
import currencies from '@/data/currency/currencies';
import currencyLocales from '@/data/locales/currency_locales';
import PgInput from '@/components/Forms/PgInput';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import TabsManager from '@/components/Tabs/TabsManager';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';

export default {
  name: 'CashierStripe',
  mixins: [asyncImports, mutations],
  components: {
    FormInputGroup,
    PgCheckBox,
    TabsManager,
    FormInputTitle,
    Draggable,
    PgLabeledInput,
    PgInput,
    Separator,
    ScaffoldingComponentContainer,
    Column,
    ContentCard,
    Row,
  },
  data() {
    return {
      loading: false,

      currency: 'USD',
      currencies: [],

      locale: 'en_US',
      currencyLocales: [],

      logger: 'stack',

      activeTab: 'subscriptions',

      singleCharges: [],
      subscriptions: [],

      deleting: [],

      singleChargeDefinition: '',
    };
  },
  async created() {
    this.currencies = currencies;
    this.currencyLocales = currencyLocales;

    this.loading = true;
    this.registerMutable('Stripe Currency', 'payments/cashier/stripe/currency', {
      then: (value) => this.currency = value || this.currency,
    });
    this.registerMutable('Stripe Currency Locale', 'payments/cashier/stripe/currency/locale', {
      then: (value) => this.locale = value || this.locale,
    });
    this.registerMutable('Stripe Logger', 'payments/cashier/stripe/logger', {
      then: (value) => this.logger = value || this.logger,
    });
    this.registerMutable('Active Tab', 'payments/cashier/stripe/tabs/active', {
      then: (value) => this.activeTab = value || this.activeTab,
    });

    await this.syncSubscriptions();
    await this.syncSingleCharges();
    this.loading = false;
  },
  computed: {
    tabs() {
      return [
        {
          id: 'subscriptions',
          label: `Subscriptions (${this.subscriptions.length})`,
        },
        {
          id: 'single-charges',
          label: `Single Charges (${this.singleCharges.length})`,
        },
      ];
    },
  },
  methods: {
    async syncSubscriptions() {
      const { data } = await this.mutation({ path: 'payments/cashier/stripe/subscription/', like: true, refresh: true });

      if (!data.value) {
        return;
      }

      this.subscriptions = data.value.map((v) => v.value);
    },

    async syncSingleCharges() {
      const { data } = await this.mutation({ path: 'payments/cashier/stripe/single-charge/', like: true, refresh: true });

      if (!data.value) {
        return;
      }

      this.singleCharges = data.value.map((v) => v.value);
    },

    persistCurrency(currency) {
      const name = 'Stripe Currency';
      const path = 'payments/cashier/stripe/currency';

      const payload = {
        name,
        path,
        value: currency,
      };

      this.mutate(payload);
    },

    persistCurrencyLocale(locale) {
      const name = 'Stripe Currency Locale';
      const path = 'payments/cashier/stripe/currency/locale';

      const payload = {
        name,
        path,
        value: locale,
      };

      this.mutate(payload);
    },

    persistLogger(logger) {
      const name = 'Stripe Logger';
      const path = 'payments/cashier/stripe/logger';

      const payload = {
        name,
        path,
        value: logger,
      };

      this.mutate(payload);
    },

    addSingleCharge(data = {}) {
      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);

      const charge = {
        id,
        name: data.name || '',
        amount: '',
      };

      this.singleCharges.push(charge);

      return charge;
    },

    addSubscription() {
      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);

      this.subscriptions.push({
        id,
        name: this.subscriptions.length ? 'Stripe Subscription' : 'Default Subscription',
        plans: [],
        features: [],
        qty: '',
        trialDays: '',
        acceptsCoupons: false,
        payUpfront: true,
      });
    },

    addSubscriptionFeature(subscription) {
      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);

      subscription.features.push({
        id,
        desc: '',
      });

      this.$nextTick(() => {
        if (this.$refs[id]) {
          this.$refs[id][0].focus();
        }
      });
    },

    async deleteSubscription(subscription) {
      const subId = subscription.id;
      const sIndex = this.subscriptions.findIndex((s) => s.id === subId);
      if (sIndex > -1) {
        this.deleting.push(subId);
        const { status } = await this.deleteMutation(`payments/cashier/stripe/subscription/${subId}`);
        if (status === 201 || status === 404) {
          this.subscriptions.splice(sIndex, 1);
          this.deleting.splice(this.deleting.indexOf(subId), 1);
        }
      }
    },

    deleteSubscriptionFeature(feature, subscription) {
      const fIndex = subscription.features.findIndex((f) => f.id === feature.id);
      if (fIndex > -1) {
        subscription.features.splice(fIndex, 1);
        this.persistSubscription(subscription);
      }
    },

    isDeleting(s) {
      return s && s.id && this.deleting.includes(s.id);
    },

    persistSubscription(subscription) {
      const name = 'Stripe Subscription';
      const path = `payments/cashier/stripe/subscription/${subscription.id}`;

      const payload = {
        name,
        path,
        value: subscription,
      };

      this.mutate(payload);
    },

    persistSingleCharge(singleCharge) {
      const name = 'Stripe Single Charge';
      const path = `payments/cashier/stripe/single-charge/${singleCharge.id}`;

      const payload = {
        name,
        path,
        value: singleCharge,
      };

      this.mutate(payload);
    },

    async deleteSingleCharge(singleCharge) {
      const chargeId = singleCharge.id;

      const sIndex = this.singleCharges.findIndex((s) => s.id === chargeId);
      if (sIndex > -1) {
        this.deleting.push(chargeId);
        const { status } = await this.deleteMutation(`payments/cashier/stripe/single-charge/${chargeId}`);
        if (status === 201) {
          this.singleCharges.splice(sIndex, 1);
          this.deleting.splice(this.deleting.indexOf(chargeId), 1);
        }
      }
    },

    onCreateSingleChargeFromInput() {
      const input = this.singleChargeDefinition.trim();

      if (input === '') {
        return;
      }

      let charge = this.singleCharges.find((s) => s.name.toLowerCase() === input.toLowerCase());

      if (charge) {
        this.singleChargeDefinition = '';
        return;
      }

      charge = this.addSingleCharge({ name: input });

      this.persistSingleCharge(charge);

      this.singleChargeDefinition = '';
    },

    onSingleChargeStateChanged(active, singleCharge) {
      if (!active) {
        this.deleteSingleCharge(singleCharge);
      }
    },
  },
};
</script>

<style scoped>

</style>
