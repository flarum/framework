var gulp = require('flarum-gulp');

gulp({
  files: [
    'bower_components/iframe-resizer/js/iframeResizer.contentWindow.min.js'
  ],
  modules: {
    'embed': 'src/**/*.js'
  }
});
