var gulp = require("gulp");
var concat = require("gulp-concat");

gulp.task("default", function() {
  gulp.src("www/js/gulp/*.js", {base: "www/js/gulp"})
    .pipe(concat("build.js"))
    .pipe(gulp.dest("www"));
  gulp.src("www/css/gulp/*.css", {base: "www/css/gulp"})
    .pipe(concat("build.css"))
    .pipe(gulp.dest("www"));
});
