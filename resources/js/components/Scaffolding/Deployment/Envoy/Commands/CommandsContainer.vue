<template>
    <row>
        <column v-if="description">
            <p class="text-primary no-margin p-l-5"><i class="fa fa-info"></i> <span v-html="description"></span></p>
        </column>
        <column :key="c.command" v-for="c in commands">
          <pg-check-box :disabled="disabled" v-model="c.enabled" color-class="complete" :label="c.command"
                        @change="handleCommandStateChange($event, c)" v-if="!c.custom"/>

          <tippy :ref="c.id"
                   interactive
                   theme="transparent"
                   placement="left"
                   :onShow="() => popupOnShow(c)"
                   :onShown="() => popupShown(c)"
                   @hidden="hidden($event, c)"
                   arrow v-if="c.custom">
                <template slot="trigger">
                    <pg-check-box :disabled="disabled" v-model="c.enabled" color-class="complete" @change="handleCommandStateChange($event, c)">
                        <template slot="label">
                            <span v-html="c.command" />
                          <a href="#" class="text-danger link small m-l-15" @click.prevent="$emit('delete', c)"
                             @focus="hidePopup(c)" @mouseenter="hidePopup(c)"><i class="fa fa-close"></i></a>
                        </template>
                    </pg-check-box>
                </template>

                <content-card heading="Edit Custom Command">
                    <pg-input :ref="`CI${c.id}`" v-model="buffer" placeholder="Command..." />
                </content-card>
            </tippy>

        </column>

        <column push5 style="margin-left: 7px;">
            <a href="#" class="text-info link" @click.prevent="$emit('new')"><i class="fa fa-plus"></i></a>
        </column>

    </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import ContentCard from '@/components/Cards/ContentCard';
import PgInput from '@/components/Forms/PgInput';

export default {
  name: 'CommandsContainer',
  props: {
    task: String,
    description: String,
    commands: Array,
    disabled: Boolean,
    toggles: String,
  },
  components: {
    PgInput, ContentCard, PgCheckBox, Column, Row,
  },
  data() {
    return {
      buffer: '',
    };
  },
  methods: {
    handleCommandStateChange(enabled, command) {
      if (command.toggles && enabled) {
        const toggleTarget = this.commands.find((c) => c.command === command.toggles);
        if (toggleTarget) {
          toggleTarget.enabled = false;
        }
      }

      this.$emit('change', command);
    },

    popupShown(c) {
      this.$nextTick(() => {
        if (this.$refs[`CI${c.id}`]) {
          this.$refs[`CI${c.id}`][0].focus();
        }
      });
    },

    popupOnShow(c) {
      this.buffer = c.command;

      return true;
    },

    hidePopup(c) {
      if (this.$refs[c.id]) {
        this.$refs[c.id][0].tip.hide();
      }
    },

    hidden(e, c) {
      this.$nextTick(() => {
        c.command = this.buffer;
      });
    },
  },
};
</script>

<style scoped>

</style>
