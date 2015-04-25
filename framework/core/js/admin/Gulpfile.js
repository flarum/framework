var gulp = require('gulp');
var livereload = require('gulp-livereload');
var concat = require('gulp-concat');
var argv = require('yargs').argv;
var uglify = require('gulp-uglify');
var gulpif = require('gulp-if');
var merge = require('merge-stream');
var babel = require('gulp-babel');
var cached = require('gulp-cached');
var remember = require('gulp-remember');

var vendorFiles = [
  './bower_components/loader.js/loader.js',
  './bower_components/mithril/mithril.js',
  './bower_components/jquery/dist/jquery.js',
  './bower_components/moment/moment.js',
  './bower_components/bootstrap/dist/js/bootstrap.js',
  './bower_components/spin.js/spin.js',
  './bower_components/spin.js/jquery.spin.js'
];

var moduleFiles = [
  'src/**/*.js',
  '../lib/**/*.js'
];
var modulePrefix = 'flarum';

gulp.task('default', function() {
  return merge(
    gulp.src(vendorFiles),
    gulp.src(moduleFiles)
      .pipe(cached('scripts'))
      .pipe(babel({ modules: 'amd', moduleIds: true, moduleRoot: modulePrefix }))
      .pipe(remember('scripts'))
  )
    .pipe(concat('app.js'))
    .pipe(gulpif(argv.production, uglify()))
    .pipe(gulp.dest('dist'))
    .pipe(livereload());
});

gulp.task('watch', ['default'], function () {
  livereload.listen();
  var watcher = gulp.watch(moduleFiles.concat(vendorFiles), ['default']);
  watcher.on('change', function (event) {
    if (event.type === 'deleted') {
      delete cached.caches.scripts[event.path];
      remember.forget('scripts', event.path);
    }
  });
});
