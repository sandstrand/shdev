'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var flatten = require('gulp-flatten');

gulp.task('sass', function() {
	// return gulp.src('./resources/assets/sass/**/*.scss')
	// 	.pipe(sass().on('error', sass.logError))
	// 	.pipe(gulp.dest('./assets/css/'));
	return gulp.src('./resources/assets/sass/**/*.scss')
		.pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
		.pipe(rename({suffix: '.min'}))
		.pipe(flatten())
		.pipe(gulp.dest('./assets/css/'));
});

gulp.task('js', function() {
	// return gulp.src('./resources/assets/javascripts/**/*.js')
	// 	.pipe(gulp.dest('./assets/js/'));
	return gulp.src('./resources/assets/javascripts/**/*.js')
		.pipe(uglify({mangle: true}))
		.pipe(rename({suffix: '.min'}))
		.pipe(flatten())
		.pipe(gulp.dest('./assets/js/'));
});

gulp.task('watch', function() {
	gulp.watch('./resources/assets/sass/**/*.scss', ['sass']);
	gulp.watch('./resources/assets/javascripts/**/*.js', ['js']);
})

gulp.task('watch:sass', function () {
	gulp.watch('./resources/assets/sass/**/*.scss', ['sass']);
});

gulp.task('watch:js', function() {
	gulp.watch('./resources/assets/javascripts/**/*.js', ['js']);
});

gulp.task('dist', function() {
	gulp.src('./resources/assets/sass/**/*.scss')
		.pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
		.pipe(rename({suffix: '.min'}))
		.pipe(flatten())
		.pipe(gulp.dest('./assets/css/'));

	gulp.src('./resources/assets/javascripts/**/*.js')
		.pipe(uglify({mangle: true}))
		.pipe(rename({suffix: '.min'}))
		.pipe(flatten())
		.pipe(gulp.dest('./assets/js/'));
});