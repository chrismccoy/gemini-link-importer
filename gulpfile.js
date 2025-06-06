// Gulp.js configuration for Gemini Link Importer

// --- 1. Require Gulp and Plugins ---
const { src, dest, watch, series, parallel } = require("gulp");
const pkg = require("./package.json");
const sass = require("gulp-sass")(require("sass"));
const cleanCSS = require("gulp-clean-css");
const uglify = require("gulp-uglify");
const rename = require("gulp-rename");
const wpPot = require("gulp-wp-pot");
const through = require("through2");

// --- 2. Define File Paths ---
const paths = {
  scss: {
    src: "assets/scss/admin.scss",
    dest: "assets/css/",
    watch: "assets/scss/**/*.scss",
  },
  js: {
    src: "assets/js/admin.js",
    dest: "assets/js/",
    watch: "assets/js/admin.js",
  },
  php: {
    src: "**/*.php",
  },
  lang: {
    src: "languages/*.po",
    dest: "languages/",
    pot: "languages/gemini-link-importer.po", // Renamed .po file
  },
};

// --- 3. Define Gulp Tasks ---

// Compile SCSS to CSS
function styles() {
  return src(paths.scss.src)
    .pipe(sass.sync({ outputStyle: "expanded" }).on("error", sass.logError))
    .pipe(dest(paths.scss.dest)) // Output the readable admin.css
    .pipe(cleanCSS())
    .pipe(rename({ suffix: ".min" }))
    .pipe(dest(paths.scss.dest)); // Output the minified admin.min.css
}

// Minify JavaScript
function scripts() {
  return src(paths.js.src)
    .pipe(uglify())
    .pipe(rename({ suffix: ".min" }))
    .pipe(dest(paths.js.dest));
}

// Generate the .po language source file
function makepot() {
  return src(paths.php.src)
    .pipe(
      wpPot({
        domain: "gemini-link-importer", // Renamed domain
        package: pkg.name,
        bugReport: "YOUR_BUG_REPORT_URL_OR_EMAIL",
        lastTranslator: "Your Name <your.email@example.com>",
        team: "English <your.email@example.com>",
        headers: {
          Language: "en_US",
          "Plural-Forms": "nplurals=2; plural=(n != 1);",
        },
      })
    )
    .pipe(dest(paths.lang.pot));
}

// Asynchronous task to compile .po files to .mo
async function po2mo() {
  const gettextParser = await import("gettext-parser");

  return src(paths.lang.src)
    .pipe(
      through.obj(function (file, enc, cb) {
        if (file.isNull()) {
          return cb(null, file);
        }

        const originalRelative = file.relative;

        try {
          const poData = gettextParser.po.parse(file.contents);
          const moBuffer = gettextParser.mo.compile(poData);
          file.contents = moBuffer;
          file.path = file.path.replace(/\.po$/, ".mo");
          console.log(`Compiled ${originalRelative} -> ${file.basename}`);
        } catch (error) {
          this.emit(
            "error",
            new Error(`Error compiling ${file.relative}: ${error.message}`)
          );
        }

        cb(null, file);
      })
    )
    .pipe(dest(paths.lang.dest));
}

// Watch for file changes and run tasks
function watchFiles() {
  watch(paths.scss.watch, styles);
  watch(paths.js.watch, scripts);
  watch(paths.php.src, makepot);
  watch(paths.lang.src, po2mo);
}

// --- 4. Define Composite and Export Tasks ---

const translate = series(makepot, po2mo);
const build = parallel(styles, scripts, translate);

exports.styles = styles;
exports.scripts = scripts;
exports.makepot = makepot;
exports.po2mo = po2mo;
exports.translate = translate;
exports.watch = watchFiles;
exports.build = build;
exports.default = build;
