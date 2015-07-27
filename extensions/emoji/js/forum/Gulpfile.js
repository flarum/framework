var gulp = require('flarum-gulp');

gulp({
  modules: {
    'emoji': 'src/**/*.js'
  },
  files: [
    'bower_components/twemoji/index.js'
  ]
});
