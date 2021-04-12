// eslint-disable-next-line import/no-extraneous-dependencies
const mix = require('laravel-mix');
const fs = require('fs');

const modules = ['main'];

const modulesPath = 'resources/js/modules/';
const modulesOutputPath = 'public/js/modules/';

modules.forEach((module) => {
  if (fs.existsSync(`${modulesPath + module}.js`)) {
    mix.js(`${modulesPath + module}.js`, modulesOutputPath);
  } else {
    const files = fs.readdirSync(modulesPath + module);
    files.forEach((file) => {
      mix.js(`${modulesPath + module}/${file}`, modulesOutputPath + module);
    });
  }
});

mix.js('resources/js/app.js', 'public/js');
