var gulp = require('gulp');
var livereload = require('gulp-livereload');
var concat = require('gulp-concat');
var argv = require('yargs').argv;
var uglify = require('gulp-uglify');
var gulpif = require('gulp-if');
var babel = require('gulp-babel');
var cached = require('gulp-cached');
var remember = require('gulp-remember');
var merge = require('merge-stream');
var streamqueue = require('streamqueue');

var staticFiles = [
  'bootstrap.js'
];
var moduleFiles = [
  'src/**/*.js'
];
var modulePrefix = 'categories';

gulp.task('default', function() {
  return streamqueue({objectMode: true},
    gulp.src(moduleFiles)
      .pipe(cached('scripts'))
      .pipe(babel({ modules: 'amd', moduleIds: true, moduleRoot: modulePrefix }))
      .pipe(remember('scripts')),
    gulp.src(staticFiles)
      .pipe(babel())
  )
    .pipe(concat('extension.js'))
    .pipe(gulpif(argv.production, uglify()))
    .pipe(gulp.dest('dist'))
    .pipe(livereload());
});

gulp.task('watch', ['default'], function () {
  livereload.listen();
  var watcher = gulp.watch(moduleFiles.concat(staticFiles), ['default']);
  watcher.on('change', function (event) {
    if (event.type === 'deleted') {
      delete cached.caches.scripts[event.path];
      remember.forget('scripts', event.path);
    }
  });
});
