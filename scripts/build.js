const fs = require('fs');
const path = require('path');
const sass = require('sass');
const postcss = require('postcss');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');
const terser = require('terser');

const ASSETS_DIR = path.resolve(__dirname, '../assets');
const JS_SRC = path.join(ASSETS_DIR, 'js', 'admin.js');
const JS_DEST_MIN = path.join(ASSETS_DIR, 'js', 'admin.min.js');
const CSS_SRC = path.join(ASSETS_DIR, 'scss', 'admin.scss');
const CSS_DEST = path.join(ASSETS_DIR, 'css', 'admin.css'); // Unminified
const CSS_DEST_MIN = path.join(ASSETS_DIR, 'css', 'admin.min.css'); // Minified

async function compileSass() {
  console.log('Compiling Sass...');
  try {
    const result = sass.compile(CSS_SRC, {
      style: 'expanded',
      sourceMap: false,
    });
    fs.writeFileSync(CSS_DEST, result.css.toString()); // Write unminified CSS
    console.log(`  Compiled ${path.basename(CSS_SRC)} to ${path.basename(CSS_DEST)}`);

    const processedCss = await postcss([autoprefixer(), cssnano({ preset: 'default' })]).process(result.css, { from: CSS_DEST, to: CSS_DEST_MIN });
    fs.writeFileSync(CSS_DEST_MIN, processedCss.css); // Write minified CSS
    console.log(`  Minified ${path.basename(CSS_DEST)} to ${path.basename(CSS_DEST_MIN)}`);
  } catch (error) {
    console.error(`Error compiling Sass: ${error.message}`);
    throw error;
  }
}

async function minifyJs() {
  console.log('Minifying JavaScript...');
  try {
    const jsCode = fs.readFileSync(JS_SRC, 'utf8');
    const result = await terser.minify(jsCode, {
      mangle: { toplevel: true },
      compress: { drop_console: true },
      output: { comments: false }, // Remove comments
    });
    if (result.code) {
        fs.writeFileSync(JS_DEST_MIN, result.code);
        console.log(`  Minified ${path.basename(JS_SRC)} to ${path.basename(JS_DEST_MIN)}`);
    } else {
        throw new Error("Terser minification failed to produce code.");
    }
  } catch (error) {
    console.error(`Error minifying JavaScript: ${error.message}`);
    throw error;
  }
}

async function buildAll() {
  console.log('--- Starting Build Process ---');
  try {
    await compileSass();
    await minifyJs();
    console.log('--- Build Process Completed Successfully ---');
  } catch (error) {
    console.error('Build process failed:', error);
    process.exit(1);
  }
}

buildAll();
