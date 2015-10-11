var gulp = require('flarum-gulp');

gulp({
  modules: {
    'flarum/emoji': 'src/**/*.js'
  },
  files: [
    'bower_components/twemoji/index.js'
  ]
});
