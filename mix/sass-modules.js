// eslint-disable-next-line import/no-extraneous-dependencies
const mix = require('laravel-mix');

// noinspection JSUnresolvedFunction
mix.sass('resources/sass/app.scss', 'public/css');
// noinspection JSUnresolvedFunction
mix.sass('resources/template/pages/scss/themes/corporate/corporate.scss', 'public/css/pages.css');
