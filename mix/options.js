// eslint-disable-next-line import/no-extraneous-dependencies
const mix = require('laravel-mix');

const config = require('../webpack.config');

// const url = process.env.APP_URL.replace(/(^\w+:|^)\/\//, '');

mix.options({
  hmrOptions: {
    host: 'localhost',
    port: 8080,
  },
});

mix.webpackConfig(config);

if (process.env.npm_lifecycle_event !== 'hot' && process.env.NODE_ENV !== 'development') {
  // noinspection JSUnresolvedFunction
  mix.version();
}

mix.disableNotifications();
