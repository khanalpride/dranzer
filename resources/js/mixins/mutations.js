import {
  mapActions, mapGetters, mapMutations, mapState,
} from 'vuex';
import debounce from 'lodash/debounce';
import paths from '../data/scaffolding/mutable';

const { axios } = window;

export default {
  data() {
    return {
      moduleMutations: [],

      mutationsProcessed: false,

      debouncedCreateMutationCB: null,

      fetchingMutation: false,

      fetchingMutations: false,

      mutating: false,

      mutated: false,

      fetchedMutations: [],

      assignments: [],

      debounced: [],
    };
  },
  computed: {
    ...mapState('project', ['mutations', 'project']),
    ...mapGetters('project', ['projectId']),

    pendingMutations() {
      return this.mutations.filter(
        (m) => m.mutating && m.projectId === this.projectId,
      ).length;
    },
  },

  async created() {
    this.debouncedCreateMutationCB = debounce(
      (payload) => this.createMutation(payload),
      500,
    );
  },

  async mounted() {
    this.mutationsProcessed = false;

    if (!this.moduleMutations || !this.moduleMutations.length) {
      // Get the mutation in case it's not registered in the component.
      // If it is registered, the getMutation method will return the
      // already fetched value returned by the batch call.
      this.$nextTick(async () => {
        if (this.mutationPath) {
          this.fetchingMutation = true;

          const { status, data } = await this.getMutation({
            name: this.mutationName,
            path: this.mutationPath,
          });

          this.fetchedMutations.push({
            name: this.mutationName,
            path: this.mutationPath,
            value: status === 200 ? data.value : null,
          });

          this.$nextTick(() => {
            this.fetchingMutation = false;
          });
        }
      });

      this.mutationsProcessed = true;

      return;
    }

    this.fetchingMutations = true;

    await this.getMutation({
      moduleMutations: this.moduleMutations,
      module: this.$options.name,
    });

    setTimeout(() => {
      this.assignments.filter(
        (a) => a.projectId === this.projectId,
      ).forEach((a) => this.assign(a.path, a.options));
    }, 50);

    setTimeout(() => {
      this.fetchingMutations = false;

      this.mutationsProcessed = true;

      this.focusDefaultInput();
    }, 75);
  },

  methods: {
    ...mapActions('project', ['createMutation', 'getMutation']),
    ...mapMutations('project', ['DELETE_MUTATION']),

    registerMutable(name, path, options) {
      const mutation = this.moduleMutations.find(
        (m) => m.path === path && m.projectId === this.projectId,
      );

      if (!mutation) {
        this.moduleMutations.push({
          name,
          path,
          module: this.$options.name,
          projectId: this.projectId,
        });
      }

      if (options && options.then) {
        this.registerAssignment(path, options);
      }
    },

    registerAssignment(path, options = {}) {
      const assignment = this.assignments.find(
        (a) => a.path === path && a.projectId === this.projectId,
      );

      if (!assignment) {
        this.assignments.push({
          path,
          options,
          projectId: this.projectId,
        });
      }
    },

    async assign(path, options) {
      const o = options || {};

      this.fetchingMutation = true;

      const { status, data } = await this.mutation({
        path: path || this.mutationPath,
      });

      if (status === 200) {
        const hasData = data.value !== null && data.value !== undefined;

        const value = hasData ? data.value : null;

        if (o.then) {
          o.then(value);
        }
      }

      this.$nextTick(() => {
        this.fetchingMutation = false;
        this.focusDefaultInput();
      });
    },

    mutate(payload) {
      const { path, value } = payload;
      const debounced = this.debounced.find((d) => d.path === path);

      let func = null;

      if (!debounced) {
        func = debounce((p) => this.createMutation(p), 500);
        this.debounced.push({
          path,
          func,
        });
      } else {
        func = debounced.func;
      }

      const name = payload.name || this.mutationName || 'Configuration';
      const mutationId = payload.mutationId || null;
      const returnMutation = payload.returnMutation !== undefined ? payload.returnMutation : false;
      const bulk = payload.bulk !== undefined ? payload.bulk : false;

      return func({
        name: name || this.mutationName,
        path: path || this.mutationPath,
        value,
        mutationId,
        returnMutation,
        bulk,
      });
    },

    async deleteMutation(path, options) {
      if (!this.project || this.project.downloaded) {
        return {
          status: 403,
          data: [],
        };
      }

      const response = await axios.delete(
        `/mutations/${this.projectId}/${path}`,
      );

      if (response.status === 201 || response.status === 404) {
        this.DELETE_MUTATION({ path });
      }

      if (!options || !options.then) {
        return response;
      }

      const { status, data } = response;

      if (status === 201 || status === 404 || status === 200) {
        options.then(status);
      }

      return { status, data };
    },

    async bulkDeleteMutations(mutationPaths, options) {
      if (!this.project || this.project.downloaded) {
        return {
          status: 403,
          data: [],
        };
      }

      const response = await axios.post(
        `/mutations/delete/bulk/${this.projectId}`, { paths: mutationPaths },
      );

      if (response.status === 201 || response.status === 404 || response.status === 200) {
        mutationPaths.forEach((path) => this.DELETE_MUTATION({ path }));
      }

      if (!options || !options.then) {
        return response;
      }

      const { status, data } = response;

      if (status === 201 || status === 404) {
        options.then(status);
      }

      return { status, data };
    },

    getPersistedMutationValue(response) {
      if (!response) {
        return null;
      }

      let resp = response;

      if (resp.data) {
        resp = resp.data;
      }

      return resp.value && Array.isArray(resp.value)
        ? resp.value.map((v) => v.value)
        : (resp.value || null);
    },

    async mutation(payload) {
      const mutation = this.mutations.find(
        (m) => m.path === payload.path && m.projectId === this.projectId,
      );
      if (mutation && mutation.fetched && !payload.refresh) {
        return {
          status: 200,
          data: {
            value: mutation.value,
          },
        };
      }

      return this.getMutation(payload);
    },

    getPendingMutationsAsString(collapse = true) {
      const mutations = this.mutations.filter((m) => m.mutating);

      if (!mutations.length) {
        return null;
      }

      if (mutations.length === 1 || collapse) {
        return mutations[0].name;
      }

      return mutations.map((m) => m.name).join(', ');
    },

    getCollapsedPendingMutationsAsString() {
      const mutations = this.mutations.filter((m) => m.mutating);

      if (mutations.length < 2) {
        return null;
      }

      return mutations
        .slice(1)
        .map((m) => m.name)
        .join(', ');
    },

    focusDefaultInput() {
      if (this.defaultFocusableInputRef) {
        this.$nextTick(() => {
          setTimeout(() => {
            if (this.$refs[this.defaultFocusableInputRef]) {
              let input = this.$refs[
                this.defaultFocusableInputRef
              ];

              if (Array.isArray(input)) {
                // eslint-disable-next-line prefer-destructuring
                input = input[0];
              }

              if (input) {
                input.focus();

                try {
                  this.$nextTick(() => {
                    // 750 is the time in ms before the tooltip is shown for most components.
                    setTimeout(() => {
                      if (input) {
                        input.hideTooltip(false);
                      }
                    }, 750);
                  });
                  // eslint-disable-next-line no-empty
                } catch (e) {

                }
              }
            }
          }, 100);
        });
      }
    },

    prepareMutationData(path) {
      const p = path || this.path;

      if (!p) {
        return;
      }

      const meta = this.getPathMeta(p);

      if (meta) {
        this.mutationName = meta.name;
        this.mutationPath = meta.path;
      }
    },

    getPathMeta(path) {
      const segments = path.split('/');

      let pathNode = paths.find((p) => p.key === segments[0]);

      segments.forEach((segment) => {
        pathNode = this.resolvePathSegment(pathNode, segment);
      });

      return pathNode;
    },

    resolvePathSegment(module, segment) {
      if (!module) {
        return module;
      }

      const iterable = module.children || module.paths;

      if (!iterable) {
        return module;
      }

      // eslint-disable-next-line no-restricted-syntax
      for (const child of iterable) {
        if (child.key === segment) {
          return child;
        }
      }

      return module;
    },
  },
};
