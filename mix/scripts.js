// eslint-disable-next-line import/no-extraneous-dependencies
const mix = require('laravel-mix');

const pluginsPath = 'resources/template/assets/plugins';
const pagesPath = 'resources/template/pages';

mix.scripts([
  `${pluginsPath}/pace/pace.min.js`,
  `${pluginsPath}/jquery/jquery-3.2.1.min.js`,
  `${pluginsPath}/modernizr.custom.js`,
  `${pluginsPath}/popper/umd/popper.min.js`,
  `${pluginsPath}/bootstrap/js/bootstrap.min.js`,
  `${pluginsPath}/jquery/jquery-easy.js`,
  `${pluginsPath}/jquery-unveil/jquery.unveil.min.js`,
  `${pluginsPath}/jquery-ios-list/jquery.ioslist.min.js`,
  `${pluginsPath}/jquery-actual/jquery.actual.min.js`,
  `${pluginsPath}/jquery-scrollbar/jquery.scrollbar.min.js`,
  `${pagesPath}/js/pages.min.js`,
], 'public/js/vendor.js');
