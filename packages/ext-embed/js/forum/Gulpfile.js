var gulp = require('flarum-gulp');

gulp({
  files: [
    'bower_components/iframe-resizer/js/iframeResizer.contentWindow.min.js'
  ],
  modules: {
    'flarum/embed': 'src/**/*.js'
  }
});
