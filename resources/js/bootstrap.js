const baseURL = document.head.querySelector('meta[name="base-url"]').content;

// eslint-disable-next-line import/no-extraneous-dependencies
const axios = require('axios');

const $ = window.jQuery;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

axios.interceptors.response.use(
  (response) => response,
  (err) => {
    if (err.response) {
      if (err.response.status === 401) {
        $('#unauthenticatedModal').modal('show');
      }
      if (err.response.status === 429) {
        const retryAfter = err.response.headers['retry-after'] || 'a few';
        $('#slowDownModalMessage').html(`You've made too many concurrent requests recently.
            Please wait for ${retryAfter} seconds and then refresh this page again.
            If these were scaffolding changes, then the last few requests (update / fetch) were rejected.
            Make sure to make those changes again before continuing.`);
        $('#slowDownModal').modal('show');
      }

      return err.response;
    }
    return err;
  },
);

axios.defaults.baseURL = baseURL;

window.axios = axios;

window.baseURL = baseURL;
