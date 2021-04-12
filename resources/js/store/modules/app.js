/* eslint-disable no-shadow,no-param-reassign,no-unused-expressions */

const { Promise, axios } = window;

/**
 *
 * @type {{totalProjects: number, projects: [], projectsFetchCount: number, asyncImports: [],
 *       projectsOrder: string, allProjectsLoaded: boolean, loadingProjects: boolean}}
 */
const state = {
  loadingProjects: false,
  projects: [],
  totalProjects: 0,
  projectsOrder: 'asc',
  allProjectsLoaded: false,
  projectsFetchCount: 0,
  asyncImports: [],
};

const mutations = {
  /**
   *
   * @param state
   * @param projects
   * @constructor
   */
  SET_PROJECTS(state, projects) {
    state.projects = projects;
  },

  /**
   *
   * @param state
   * @param totalProjects
   * @constructor
   */
  SET_TOTAL_PROJECTS(state, totalProjects) {
    state.totalProjects = totalProjects;
  },

  /**
   *
   * @param state
   * @param loaded
   * @constructor
   */
  SET_ALL_PROJECTS_LOADED(state, loaded) {
    state.allProjectsLoaded = loaded;
  },

  /**
   *
   * @param state
   * @param count
   * @constructor
   */
  SET_PROJECTS_FETCH_COUNT(state, count) {
    state.projectsFetchCount = count;
  },

  /**
   *
   * @param state
   * @constructor
   */
  REVERSE_PROJECTS(state) {
    state.projects = state.projects.reverse();
  },

  /**
   *
   * @param state
   * @param order
   * @constructor
   */
  SET_PROJECTS_ORDER(state, order) {
    state.projectsOrder = order;
  },

  /**
   *
   * @param state
   * @param loading
   * @constructor
   */
  SET_LOADING_PROJECTS(state, loading) {
    state.loadingProjects = loading;
  },

  /**
   *
   * @param state
   * @param project
   * @constructor
   */
  ADD_PROJECT(state, project) {
    state.projectsOrder === 'asc'
      ? state.projects.push(project)
      : state.projects.unshift(project);
    state.totalProjects += 1;
  },

  /**
   *
   * @param state
   * @param project
   * @constructor
   */
  REMOVE_PROJECT(state, project) {
    const projectIndex = state.projects.findIndex(
      (p) => p.uuid === project.uuid,
    );

    if (projectIndex < 0) {
      return;
    }

    state.projects.splice(projectIndex, 1);
    state.totalProjects -= 1;
  },

  /**
   *
   * @param state
   * @param module
   * @constructor
   */
  ADD_ASYNC_IMPORT(state, module) {
    const imp = state.asyncImports.find((a) => a.module === module);
    if (!imp) {
      state.asyncImports.push({
        module,
        pending: true,
      });
    }
  },

  /**
   *
   * @param state
   * @param module
   * @constructor
   */
  ASYNC_IMPORT_COMPLETE(state, module) {
    const imp = state.asyncImports.find((a) => a.module === module);
    if (imp) {
      imp.pending = false;
    }
  },
};

/**
 *
 * @type {{toggleProjectsOrder(*, *): Promise<void>,
 *          getProjects(*, *=): Promise<undefined|boolean>}}
 */
const actions = {
  /**
   *
   * @param context
   * @returns {Promise<boolean>}
   */
  async getProjects(context) {
    context.commit('SET_LOADING_PROJECTS', true);

    const response = await axios.post('/projects');

    if (response.status !== 200) {
      return Promise.resolve(false);
    }

    const { data } = response;

    context.commit('SET_LOADING_PROJECTS', false);

    context.commit('SET_PROJECTS_ORDER', data.order);

    context.commit('SET_TOTAL_PROJECTS', data.total);

    const projects = data.projects || [];

    context.commit('SET_PROJECTS', projects);

    return Promise.resolve(true);
  },

  /**
   *
   * @param context
   * @param payload
   * @returns {Promise<void>}
   */
  async toggleProjectsOrder(context, payload) {
    const order = context.state.projectsOrder;

    context.commit('SET_PROJECTS_ORDER', payload.value);
    context.commit('REVERSE_PROJECTS');

    const { status } = await axios.post('/settings', {
      name: payload.name,
      value: payload.value,
    });

    if (status !== 200) {
      context.commit('SET_PROJECTS_ORDER', order);
      context.commit('REVERSE_PROJECTS');
    }
  },
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
};
