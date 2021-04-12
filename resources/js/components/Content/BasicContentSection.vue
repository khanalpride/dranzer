<template>
  <row>
    <column v-if="prependSeparator">
      <separator />
    </column>
    <column :centered="centered">
      <p
        class="no-margin"
        :class="[headingColor]"
      >
        <slot name="heading">
          <span
            class="bold"
            v-html="heading"
          />
          <tippy
            v-if="hasHelpSlot"
            style="display: inline-flex !important"
          >
            <template slot="trigger">
              <span class="text-danger bold">
                <i class="fa fa-question-circle" />
              </span>
            </template>
            <slot name="help" />
          </tippy>
        </slot>
      </p>
      <slot name="sub-heading" />
    </column>
    <column v-if="appendSeparator">
      <separator />
    </column>
    <column>
      <slot />
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import Separator from '@/components/Layout/Separator';

export default {
  name: 'BasicContentSection',
  components: { Separator, Column, Row },
  props: {
    heading: {
      type: String,
      default: null,
    },
    appendSeparator: {
      type: Boolean,
      default: true,
    },
    headingColor: {
      type: String,
      default: 'text-primary',
    },
    centered: {
      type: Boolean,
      default: true,
    },
    prependSeparator: Boolean,
  },
  computed: {
    hasHelpSlot() {
      return this.$slots.help;
    },
  },
};
</script>

<style scoped></style>
