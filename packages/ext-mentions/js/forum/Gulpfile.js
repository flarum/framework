var gulp = require('flarum-gulp');

gulp({
  modules: {
    'mentions': 'src/**/*.js'
  },
  files: [
    'bower_components/textarea-caret-position/index.js'
  ]
});
