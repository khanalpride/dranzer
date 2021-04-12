import Vue from 'vue';

// noinspection SpellCheckingInspection
import ElCascader, {
  Card,
  Timeline,
  TimelineItem,
  Select,
  OptionGroup,
  Option,
  RadioGroup,
  Radio,
  Checkbox,
  CheckboxGroup,
  Collapse,
  CollapseItem,
  Tabs,
  TabPane,
  Upload,
  Tree,
  InputNumber,
  Table,
  TableColumn,
  ColorPicker,
} from 'element-ui';

import lang from 'element-ui/lib/locale/lang/en';
import locale from 'element-ui/lib/locale';

import VueTippy, { TippyComponent } from 'vue-tippy';

import App from '@/views/App';

import MainPolyFills from '@/modules/polyfills/main_polyfills';
import MainPrototypes from '@/modules/prototypes/main_prototypes';
import MainDirectives from '@/modules/directives/main_directives';

import router from '../router';
import store from '../store';

locale.use(lang);

Vue.use(Card);
Vue.use(Timeline);
Vue.use(TimelineItem);
Vue.use(Select);
Vue.use(OptionGroup);
Vue.use(Option);
Vue.use(RadioGroup);
Vue.use(Radio);
Vue.use(Checkbox);
Vue.use(CheckboxGroup);
Vue.use(Collapse);
Vue.use(CollapseItem);
Vue.use(Tabs);
Vue.use(TabPane);
Vue.use(Upload);
Vue.use(Tree);
Vue.use(InputNumber);
Vue.use(Table);
Vue.use(TableColumn);
Vue.use(ColorPicker);

VueTippy.install(Vue, {
  arrow: true,
});
Vue.component('Tippy', TippyComponent);

MainPolyFills.init();
MainPrototypes.init(Vue);
MainDirectives.init(Vue);

ElCascader.install(Vue);

new Vue({
  store,
  router,
  render: (h) => h(App),
}).$mount('#app');

window.Vue = Vue;
