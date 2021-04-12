export default {
  created() {
    this.entity = this.syncEntity(this.persisted, this.entity);
  },
  methods: {
    /**
     *
     * @param persisted
     * @param newObject
     * @returns {any}
     */
    syncEntity(persisted, newObject) {
      return JSON.parse(JSON.stringify({ ...newObject, ...(persisted || {}) }));
    },
  },
};
