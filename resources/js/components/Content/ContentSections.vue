<template>
  <el-collapse
    v-model="active"
    :accordion="accordion"
  >
    <el-collapse-item
      v-for="section in sections"
      :key="section.name"
      :name="section.name"
    >
      <template slot="title">
        <span
          class="bold"
          :class="{
            'text-info hint-text': active !== section.name,
            'text-info': active === section.name
          }"
        >
          <span
            v-tippy="{
              placement: 'right',
              distance: 25,
              onShow: () => section.tooltip !== undefined
            }"
            :content="section.tooltip"
            v-html="section.title"
          />
        </span>
      </template>
      <row class="p-l-5 p-r-5">
        <column push15>
          <slot :name="section.name" />
        </column>
      </row>
    </el-collapse-item>
  </el-collapse>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';

export default {
  name: 'ContentSections',
  components: { Column, Row },
  props: {
    sections: {
      type: Array,
      required: true,
    },
    accordion: {
      type: Boolean,
      default: true,
    },
    activeSection: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      active: this.activeSection,
    };
  },
  mounted() {
    if (!this.activeSection && this.sections && this.sections.length) {
      this.active = this.sections[0].name;
    }
  },
};
</script>

<style scoped></style>
