<template>
  <row>
    <column>
      <stick-up-modal
        :ref="id"
        dismissible
      >
        <row push10>
          <column>
            <h5
              v-if="modalHeading"
              v-html="modalHeading"
            />
          </column>
          <column>
            <separator />
          </column>
          <column>
            <slot />
          </column>
        </row>
      </stick-up-modal>
    </column>
    <column centered>
      <a
        href="#"
        class="text-green link bold"
        @click.prevent="showUsageModal"
      >
        <i class="fa fa-question-circle" /> What is this section and
        how do I use it?
      </a>
    </column>
    <column>
      <separator />
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import StickUpModal from '@/components/Modals/StickUpModal';
import Separator from '@/components/Layout/Separator';

export default {
  name: 'UsageInstructions',
  components: {
    Separator, StickUpModal, Column, Row,
  },
  props: {
    modalHeading: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
    };
  },
  methods: {
    showUsageModal() {
      this.$refs[this.id].show();
    },
  },
};
</script>

<style scoped></style>
