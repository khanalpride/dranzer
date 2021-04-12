<template>
    <div :class="`radio radio-${colorClass} no-margin`" :value="value">
        <template class="inline" :id="option.id" v-for="option in options">
            <!--suppress HtmlFormInputWithoutLabel -->
            <input type="radio"
                   v-model="activeOption"
                   :key="option.id"
                   :value="option.id || option.value" :name="groupId"
                   :id="option.id || option.value" @change="onInputChange" />
            <label :key="option.id" :for="option.id || option.value">
                {{ option.label || option.text || option.title }}
            </label>
        </template>
    </div>
</template>

<script>
export default {
  name: 'PgRadio',
  props: {
    value: {},
    colorClass: {
      type: String,
      default: 'complete',
    },
    options: Array,
  },
  data() {
    return {
      groupId: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
      activeOption: this.value,
    };
  },
  watch: {
    value: {
      handler(v) {
        this.activeOption = v || this.activeOption;
      },
    },
  },
  methods: {
    onInputChange() {
      this.$nextTick(() => {
        this.$emit('input', this.activeOption);
        this.$emit('change', this.activeOption);
      });
    },
  },
};
</script>

<style scoped>

</style>
