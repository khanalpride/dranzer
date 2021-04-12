/* eslint-disable no-param-reassign */
import FilesystemHelpers from '@/helpers/filesystem_helpers';
import StringHelpers from '@/helpers/string_helpers';
import RegexHelpers from '@/helpers/regex_helpers';
import PromiseHelpers from '@/helpers/promise_helpers';

const { document } = window;

export default class MainPrototypes {
  static init(Vue) {
    Vue.prototype.fs = FilesystemHelpers;
    Vue.prototype.str = StringHelpers;
    Vue.prototype.rgx = RegexHelpers;
    Vue.prototype.promises = PromiseHelpers;

    Vue.prototype.app = window.app;
    Vue.prototype.env = window.env;

    Vue.prototype.asset = (asset) => `${window.baseURL}/${asset}`;

    Vue.prototype.title = (title, appendAppName = true) => {
      document.querySelector('title').innerHTML = !title
        ? window.app.name
        : title + (appendAppName ? ` - ${window.app.name}` : '');
    };
  }
}
