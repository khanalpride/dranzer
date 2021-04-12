// eslint-disable-next-line import/no-extraneous-dependencies
const mix = require('laravel-mix');

const pluginsPath = 'resources/template/assets/plugins';
const pagesPath = 'resources/template/pages';

mix.styles([
  `${pluginsPath}/pace/pace-theme-flash.css`,
  `${pluginsPath}/bootstrap/css/bootstrap.min.css`,
  `${pluginsPath}/font-awesome/css/font-awesome.css`,
  `${pluginsPath}/jquery-scrollbar/jquery.scrollbar.css`,
  `${pluginsPath}/select2/css/select2.min.css`,
  `${pluginsPath}/switchery/css/switchery.min.css`,
  `${pagesPath}/css/pages-icons.css`,
], 'public/css/vendor.css');
