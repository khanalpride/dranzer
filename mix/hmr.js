const mix = require('laravel-mix');
const path = require('path');

mix.webpackConfig({
  devServer: {
    disableHostCheck: true,
    contentBase: path.resolve(__dirname, 'public'),
  },
});
