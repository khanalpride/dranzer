<template>
  <div>
    <row v-if="loading">
      <column push5 size="3">
        <indeterminate-progress-bar />
      </column>
    </row>
    <row v-else>
      <column push5>
        <row>
          <column>
            <pg-check-box :value="1"
                          no-margin
                          :disabled="isDeleting(mailable)"
                          @change="onMailableStateChanged($event)">
              <template slot="label">
                {{ str.ellipse(mailable.name, 30) }}
                <span class="m-l-5 text-complete">
                  <i class="fa fa-file"></i>
                  emails/{{ str.snakeCase(str.studly(mailable.name)).replace(/_/g, '/') }}.blade.php
                </span>
              </template>
            </pg-check-box>
          </column>
          <column push5 size="11" class="m-l-30">
            <row>
              <column>
                <pg-check-box v-model="mailable.testRoute" no-margin label="Create Test Route" />
              </column>
              <column>
                <pg-check-box v-model="mailable.passAuthenticated" no-margin label="Pass Authenticated User" />
              </column>
              <column>
                <pg-check-box v-model="mailable.markdown" no-margin label="Markdown" />
              </column>
              <column>
                <basic-content-section heading="Type Hint">
                  <row>
                    <column v-if="visibleModels.length">
                      <pg-check-box no-margin
                                    :value="isTypeHinted(model)"
                                    :label="str.ellipse(model.modelName, 15)"
                                    :key="model.id"
                                    v-for="model in visibleModels" @change="onTypeHintedModelsChanged($event, model)" />
                    </column>
                  </row>
                </basic-content-section>
              </column>
              <column v-if="mailable.markdown">
                <row>
                  <column>
                    <basic-content-section heading="Message" prepend-separator>
                      <row>
                        <column>
                          <a href="#"
                             class="text-green small link"
                             @click.prevent="onAddComponent('custom')">
                            Add Text
                          </a>
                          <a href="#"
                             class="text-info small link m-l-5"
                             @click.prevent="onAddComponent('line')">
                            Add Empty Line
                          </a>
                          <a href="#"
                             class="text-primary small link m-l-5"
                             @click.prevent="onAddComponent('panel')">
                            Add Panel Component
                          </a>
                          <a href="#"
                             class="text-complete small link m-l-5"
                             @click.prevent="onAddComponent('button')">
                            Add Button Component
                          </a>
                          <a href="#"
                             class="text-success small m-l-5"
                             @click.prevent="onAddComponent('footer')">
                            Add Footer Text
                          </a>
                        </column>
                      </row>
                      <row push15 v-if="mailable.markdownMessage.items.length">
                        <column>
                          <draggable v-model="mailable.markdownMessage.items" group>
                            <row :key="item.id" v-for="item in mailable.markdownMessage.items">
                              <column>
                                <row>
                                  <column v-if="item.type === 'custom'">
                                    <form-input-group compact>
                                      <simple-button color-class="green" max-height class="no-padding"
                                              style="width:6px !important;"
                                              @click="onAddComponent('custom', item)"></simple-button>
                                      <pg-input v-model="item.value" placeholder="Enter markdown..."
                                                @keyup.native.enter="onAddComponent('custom', item)" />
                                      <simple-button color-class="danger" max-height @click="onRemoveComponent(item)">
                                        <i class="fa fa-close"></i>
                                      </simple-button>
                                    </form-input-group>
                                  </column>
                                  <column v-if="item.type === 'panel'">
                                    <form-input-group compact>
                                      <simple-button color-class="primary" max-height class="no-padding"
                                              style="width:6px !important;" @click.prevent="onAddComponent('panel', item)"></simple-button>
                                      <pg-input v-model="item.value" placeholder="Enter markdown..."
                                                @keyup.native.enter="onAddComponent('panel')" />
                                      <simple-button color-class="danger" max-height @click="onRemoveComponent(item)">
                                        <i class="fa fa-close"></i>
                                      </simple-button>
                                    </form-input-group>
                                  </column>
                                  <column v-if="item.type === 'button'">
                                    <form-input-group compact>
                                      <simple-button color-class="complete" max-height class="no-padding"
                                              style="width:6px !important;"
                                              @click.prevent="onAddComponent('button', item)"></simple-button>
                                      <pg-input v-model="item.label" placeholder="Label" />
                                      <simple-button color-class="danger" max-height @click="onRemoveComponent(item)">
                                        <i class="fa fa-close"></i>
                                      </simple-button>
                                    </form-input-group>
                                  </column>
                                  <column v-if="item.type === 'line'">
                                    <form-input-group compact>
                                      <simple-button color-class="info" max-height class="no-padding"
                                              style="width:6px !important;" @click="onAddComponent('line', item)"></simple-button>
                                      <pg-input read-only placeholder="Empty Line" />
                                      <simple-button color-class="danger" max-height @click="onRemoveComponent(item)">
                                        <i class="fa fa-close"></i>
                                      </simple-button>
                                    </form-input-group>
                                  </column>
                                </row>
                              </column>
                            </row>
                          </draggable>
                        </column>
                      </row>
                    </basic-content-section>
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

<!--suppress JSIncompatibleTypesComparison -->
<script>
import Draggable from 'vuedraggable';
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import mutations from '@/mixins/mutations';
import entity from '@/mixins/entity';
import PgInput from '@/components/Forms/PgInput';
import FormInputGroup from '@/components/Forms/Grouped/FormInputGroup';
import BasicContentSection from '@/components/Content/BasicContentSection';
import sharedMutations from '@/mixins/shared_mutations';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import { mapState } from 'vuex';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';

export default {
  name: 'Mailable',
  mixins: [mutations, entity, sharedMutations],
  props: {
    persisted: {},
    blueprints: Array,
  },
  components: {
    SimpleButton,
    IndeterminateProgressBar,
    BasicContentSection,
    Draggable,
    FormInputGroup,
    PgInput,
    PgCheckBox,
    Row,
    Column,
  },
  data() {
    return {
      loading: false,

      deleting: [],

      mailable: {},
    };
  },
  computed: {
    ...mapState('project', ['project']),

    visibleModels() {
      return this.blueprints.filter((s) => s.visible !== false);
    },
  },
  watch: {
    mailable: {
      handler(v) {
        this.$emit('updated', v);
      },
      deep: true,
    },
  },
  created() {
    this.loading = true;
    this.sync();
    this.loading = false;
  },
  methods: {
    sync() {
      this.mailable = this.syncEntity(this.persisted, { ...this.mailable });
    },

    isDeleting(mailable) {
      return this.deleting.includes(mailable.id);
    },

    isTypeHinted(model) {
      return this.mailable.typeHint.find((t) => t.id === model.id) !== undefined;
    },

    onMailableStateChanged(active) {
      if (!active) {
        this.$emit('delete');
      }
    },

    onAddComponent(type, callingComponent = {}) {
      if (this.project && this.project.downloaded) {
        return;
      }

      const markdownMessageItems = this.mailable.markdownMessage.items;

      let callerIndex = markdownMessageItems.findIndex((i) => i.id === callingComponent.id);

      callerIndex = callerIndex > -1 ? callerIndex + 1 : markdownMessageItems.length;

      switch (type) {
        case 'custom':
          markdownMessageItems.splice(callerIndex, 0, {
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            type: 'custom',
            value: '',
          });
          break;
        case 'panel':
          markdownMessageItems.splice(callerIndex, 0, {
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            type: 'panel',
            value: '',
          });
          break;
        case 'button':
          markdownMessageItems.splice(callerIndex, 0, {
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            type: 'button',
            label: '',
          });
          break;
        case 'line':
          markdownMessageItems.splice(callerIndex, 0, {
            id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
            type: 'line',
          });
          break;
        case 'footer':
          // noinspection JSIncompatibleTypesComparison
          // eslint-disable-next-line no-case-declarations
          const footerThanksIndex = markdownMessageItems.findIndex((i) => i.id === 'footer.thanks');

          if (footerThanksIndex > -1) {
            markdownMessageItems.splice(footerThanksIndex, 1);
          }

          // noinspection JSIncompatibleTypesComparison
          // eslint-disable-next-line no-case-declarations
          const footerAppNameIndex = markdownMessageItems.findIndex((i) => i.id === 'footer.app.name');

          if (footerAppNameIndex > -1) {
            markdownMessageItems.splice(footerAppNameIndex, 1);
          }

          markdownMessageItems.push({
            id: 'footer.thanks',
            type: 'custom',
            value: 'Thanks, <br/>',
          });
          markdownMessageItems.push({
            id: 'footer.app.name',
            type: 'custom',
            value: '{{ config(\'app.name\') }}',
          });
          break;

        default:
          break;
      }
    },

    onRemoveComponent(component) {
      const compIndex = this.mailable.markdownMessage.items.findIndex((i) => i.id === component.id);

      if (compIndex > -1) {
        this.mailable.markdownMessage.items.splice(compIndex, 1);
      }
    },

    onTypeHintedModelsChanged(active, model) {
      const typeHintedModelIndex = this.mailable.typeHint.findIndex((t) => t.id === model.id);

      if (active) {
        if (typeHintedModelIndex < 0) {
          this.mailable.typeHint.push({ id: model.id, name: model.modelName });
        } else {
          this.mailable.typeHint[typeHintedModelIndex] = { id: model.id, name: model.modelName };
        }
      } else if (typeHintedModelIndex > -1) {
        this.mailable.typeHint.splice(typeHintedModelIndex, 1);
      }
    },
  },
};
</script>

<style scoped>

</style>
