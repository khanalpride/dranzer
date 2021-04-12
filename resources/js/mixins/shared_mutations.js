export default {
  methods: {
    async fetchControllers(refresh = true) {
      const { data } = await this.mutation({
        path: 'controllers/web/',
        like: true,
        refresh,
      });
      if (data.value) {
        return data.value
          .map((c) => c.value)
          .filter((c) => c.name);
      }

      return [];
    },

    async assignControllers(refresh = true) {
      this.controllers = await this.fetchControllers(refresh);
    },

    async fetchBlueprints(refresh = true) {
      const { data } = await this.mutation({
        path: 'database/blueprints/',
        like: true,
        refresh,
      });

      if (data.value) {
        return data.value.map((m) => m.value);
      }

      return [];
    },

    async assignBlueprints(refresh = true) {
      this.blueprints = await this.fetchBlueprints(refresh);
    },

    async getNewMutationId(name, moduleName) {
      const response = await this.mutate(
        {},
        name,
        `temp/${moduleName}`,
      );
      return response && response.data ? response.data.uuid : null;
    },
  },
};
