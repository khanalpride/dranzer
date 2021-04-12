// eslint-disable-next-line import/no-extraneous-dependencies
const mix = require('laravel-mix');

mix.copy('resources/images', 'public/images');
mix.copy('resources/template/assets/img', 'public/images');
mix.copy('resources/template/pages/img', 'public/images');
mix.copy('resources/template/pages/img/progress', 'public/images');
mix.copy('resources/template/assets/plugins/font-awesome/fonts', 'public/fonts');
mix.copy('resources/template/pages/fonts', 'public/fonts');

mix.copy('resources/images/favicon.ico', 'public');
mix.copy('resources/images/favicon-16x16.png', 'public');
mix.copy('resources/images/favicon-32x32.png', 'public');
mix.copy('resources/template/assets/img/logo.png', 'public');
