import { mapMutations, mapState } from 'vuex';

export default {
  data() {
    return {
      moduleLoaded: false,
    };
  },
  computed: {
    ...mapState('app', ['asyncImports']),
  },
  mounted() {
    this.markAsyncImportAsResolved(this.$options.name);

    this.$nextTick(() => {
      this.$emit('mounted');
    });
  },
  methods: {
    ...mapMutations('app', ['ADD_ASYNC_IMPORT', 'ASYNC_IMPORT_COMPLETE']),
    addAsyncImport(module) {
      this.ADD_ASYNC_IMPORT(module);
    },
    markAsyncImportAsResolved(module) {
      this.ASYNC_IMPORT_COMPLETE(module);
    },
    importPending(module) {
      const imp = this.asyncImports.find((a) => a.module === module);
      return imp && imp.pending;
    },
  },
};
