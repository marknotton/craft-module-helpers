'use strict';

const gulp     = require('gulp');
const plumber  = require('gulp-plumber');

gulp.task('compile', () => {
  return gulp.src([
    'node_modules/on-window-resize/on-window-resize.js'
  ])
  .pipe(gulp.dest('src/vendor'))
});

gulp.task('default', ['compile']);
