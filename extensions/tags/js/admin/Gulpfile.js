var gulp = require('flarum-gulp');

gulp({
  modules: {
    'tags': [
      '../lib/**/*.js',
      'src/**/*.js'
    ]
  }
});
