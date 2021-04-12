export default {
  props: {
    size: {
      type: [String, Number],
      default: '12',
    },
    offset: [String, Number],

    push5: [Boolean, Number],
    push10: [Boolean, Number],
    push15: [Boolean, Number],
    push20: [Boolean, Number],
    push25: [Boolean, Number],
    push30: [Boolean, Number],
    push35: [Boolean, Number],
    push40: [Boolean, Number],
    push45: [Boolean, Number],
    push50: [Boolean, Number],

    centered: Boolean,
  },
  computed: {
    push() {
      for (let i = 5; i <= 50; i += 5) {
        if (this[`push${i}`] === true || this[`push${i}`] === 1) {
          return `m-t-${i}`;
        }
      }

      return '';
    },
  },
};
