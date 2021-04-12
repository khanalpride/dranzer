/* eslint-disable no-shadow,no-param-reassign,no-underscore-dangle */
/* eslint-disable no-restricted-syntax,no-prototype-builtins */

const { axios } = window;

/**
 *
 * @type {{mutations: [], project: {}, modules: []}}
 */
const state = {
  project: {},
  mutations: [],
  modules: [],
  projectComponent: null,
};

/**
 *
 * @param path
 * @param projectId
 * @param mutationId
 * @returns {*}
 */
const getSyncedMutation = (path, projectId, mutationId) => {
  if (mutationId) {
    return state.mutatios.find((m) => m.path === path
        && m.projectId === projectId
        && m.id === mutationId);
  }
  return state.mutations.find((m) => m.path === path && m.projectId === projectId);
};

/**
 *
 * @type {{schemas: (function(*): *), projectId: (function(*): number|*|null),
 *          syncedMutation: (function(*, *): *)}}
 */
const getters = {
  /**
   *
   * @param state
   * @returns {number|*|null}
   */
  projectId: (state) => (state.project ? state.project.uuid : null),
  projectComponent: (state) => state.projectComponent,

  /**
   *
   * @param state
   * @param payload
   * @returns {*}
   */
  syncedMutation: (state, payload) => getSyncedMutation(payload.path, state.project.uuid),

  /**
   *
   * @param state
   */
  schemas: (state) => state.mutations
    .filter(
      (m) => m.path
          && typeof m.path === 'string'
          && m.path.indexOf('database/schemas/') > -1,
    )
    .filter((s) => s.value && (s.value.modelName || s.value.tableName)),
};

/**
 *
 * @type {{CREATE_MUTATION(*, *): void, MUTATION_COMPLETE(*, *):
 *              void, ADD_MODULE(*, *=): undefined, SET_PROJECT(*, *): void}}
 */
const mutations = {
  /**
   *
   * @param state
   * @param project
   * @constructor
   */
  SET_PROJECT(state, project) {
    state.project = project;
  },

  SET_PROJECT_NAME(state, newName) {
    state.project = { ...state.project, ...{ name: newName } };
  },

  SET_PROJECT_COMPONENT(state, component) {
    state.projectComponent = component;
  },

  /**
   *
   * @param state
   * @param payload
   * @constructor
   */
  CREATE_MUTATION(state, payload) {
    const mutation = getSyncedMutation(
      payload.path,
      state.project.uuid,
      payload.mId,
    );
    if (!mutation) {
      state.mutations.push({
        id: payload.mId || null,
        name: payload.name,
        path: payload.path,
        value: payload.value,
        projectId: state.project.uuid,
        _mutating: true,
        // Only set to true when mutating exceeds a certain amount of time.
        // Useful for display purposes (don't wanna show indicators if it's fast).
        mutating: false,
        fetching: false,
        fetched: false,
        upsertCancel: null,
        fetchCancel: null,
      });
    }
  },

  DELETE_MUTATION(state, payload) {
    const mutationIndex = state.mutations.findIndex((m) => m.path === payload.path);
    if (mutationIndex > -1) {
      state.mutations.splice(mutationIndex, 1);
    } else {
      // Find the mutation inside the mutations' value (the value is an array)
      const mutationIndex = state.mutations.findIndex(
        (m) => (Array.isArray(m.value)
          ? m.value.find((v) => v.path === payload.path)
          : false),
      );
      if (mutationIndex > -1) {
        const mutation = state.mutations[mutationIndex];
        const childMutationIndex = mutation.value.findIndex((m) => m.path === payload.path);
        if (childMutationIndex > -1) {
          mutation.value.splice(childMutationIndex, 1);
          state.mutations[mutationIndex] = mutation;
        }
      }
    }
  },

  /**
   *
   * @param state
   * @param payload
   * @constructor
   */
  MUTATION_COMPLETE(state, payload) {
    const mutation = getSyncedMutation(payload.path, state.project.uuid);
    if (mutation) {
      mutation._mutating = false;
      mutation.mutating = false;
    }
  },

  /**
   *
   * @param state
   * @param name
   * @constructor
   */
  ADD_MODULE(state, name) {
    const module = state.modules.find((m) => m.name === name);

    if (module) {
      return;
    }

    state.modules.push({
      name,
      cancel: null,
    });
  },
};

/**
 *
 * @type {{createMutation(any, any=):
 *          Promise<{data: any, status: number}>, getMutation(any, any):
 *          Promise<{data: {}, status: number} |
 *          {data: any, status: number} |
 *          {data: {id: any, value: null | any}, status: number} |
 *          {data: any, status: number}>}}
 */
const actions = {
  async createMutation(context, payload) {
    if (!context.state.project || context.state.project.downloaded) {
      return {
        status: 429,
        data: {},
      };
    }

    const { mutationId, returnMutation } = payload;

    if (payload.mutationId) {
      delete payload.mutationId;
    }

    context.commit('CREATE_MUTATION', payload);

    const mutation = getSyncedMutation(
      payload.path,
      context.state.project.uuid,
      payload.mutationId,
    );

    if (mutation.upsertCancel) {
      mutation.upsertCancel('Aborting previous request...');
    }

    mutation._mutating = true;

    let mutated = false;

    setTimeout(() => {
      if (!mutated) {
        mutation.mutating = true;
      }
    }, 1500);

    const { status, data } = await axios.post(
      '/mutations',
      {
        name: payload.name,
        path: payload.path,
        value: payload.value,
        projectId: context.state.project.uuid,
        bulk: payload.bulk !== undefined ? payload.bulk : false,
        mutationId,
        returnMutation,
      },
      {
        cancelToken: new axios.CancelToken((c) => {
          mutation.upsertCancel = c;
        }),
      },
    );

    mutated = true;

    if (status === 200) {
      mutation.id = payload.uuid || data.uuid;
      mutation.value = payload.value;
      context.commit('MUTATION_COMPLETE', payload);
    }

    return { status, data };
  },

  /**
   *
   * @param context
   * @param payload
   * @returns {Promise<{data: {id: *, value: (null|*)}, status: number} |
   *            {data: any, status: number}|{data: {}, status: number}>}
   */
  async getMutation(context, payload) {
    if (payload.moduleMutations) {
      const mutations = payload.moduleMutations;

      const pendingMutations = [];

      mutations.forEach((mutation) => {
        const synced = getSyncedMutation(
          mutation.path,
          context.state.project.uuid,
        );
        if (!synced) {
          pendingMutations.push({
            name: mutation.name,
            path: mutation.path,
            projectId: context.state.project.uuid,
          });

          state.mutations.push({
            id: payload.mId || null,
            name: mutation.name,
            path: mutation.path,
            value: null,
            projectId: context.state.project.uuid,
            _mutation: false,
            mutating: false,
            fetching: true,
            fetched: false,
            upsertCancel: null,
            fetchCancel: null,
          });
        }
      });

      if (!pendingMutations.length) {
        return {
          status: 200,
          data: {},
        };
      }

      let module = context.state.modules.find((m) => m.name === payload.module);

      if (module) {
        if (module.cancel) {
          module.cancel('Aborting previous request in favor of new request...');
        }
      } else {
        context.commit('ADD_MODULE', payload.module);
        module = context.state.modules.find((m) => m.name === payload.module);
      }

      const { status, data } = await axios.post(
        '/mutations/fetch/batch',
        {
          mutations: pendingMutations,
          projectId: context.state.project.uuid,
        },
        {
          cancelToken: new axios.CancelToken((c) => {
            module.cancel = c;
          }),
        },
      );

      if (status === 200) {
        const mapped = data.mutations;

        for (const path in mapped) {
          if (!mapped.hasOwnProperty(path)) {
            // eslint-disable-next-line no-continue
            continue;
          }

          const uuid = mapped[path].uuid || null;
          const { value } = mapped[path];

          const synced = getSyncedMutation(path, context.state.project.uuid);
          synced.id = uuid;
          synced.value = value;
          synced.fetched = true;
        }
      }

      return { status, data };
    }

    let mutation = payload.refresh
      ? null
      : getSyncedMutation(payload.path, context.state.project.uuid);

    if (mutation) {
      return {
        status: 200,
        data: {
          id: mutation.id,
          value: mutation.value,
        },
      };
    }

    mutation = {
      id: payload.mId || null,
      name: payload.name || null,
      path: payload.path,
      value: null,
      projectId: context.state.project.uuid,
      mutating: false,
      fetching: true,
      fetched: false,
      upsertCancel: null,
      fetchCancel: null,
    };

    state.mutations.push(mutation);

    if (mutation && mutation.cancel) {
      mutation.fetchCancel(
        'Aborting previous request in favor of new request...',
      );
    }

    const { status, data } = await axios.post(
      '/mutations/fetch/single',
      {
        path: payload.path,
        projectId: payload.projectId || context.state.project.uuid,
        like: payload.like,
      },
      {
        cancelToken: new axios.CancelToken((c) => {
          mutation.fetchCancel = c;
        }),
      },
    );

    mutation.fetched = true;

    if (status === 200) {
      mutation.id = data.uuid;
      mutation.value = data.value;
      mutation.fetching = false;
    }

    return { status, data };
  },
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions,
};
