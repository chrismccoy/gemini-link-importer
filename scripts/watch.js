const chokidar = require('chokidar');
const { exec } = require('child_process'); // For running npm scripts

const paths = {
  scss: 'assets/scss/**/*.scss',
  js: 'assets/js/admin.js',
  php: '**/*.php',
  po: 'languages/*.po',
};

console.log('--- Starting Watch Process ---');
console.log('Watching for changes in:');
console.log(`  - SCSS: ${paths.scss}`);
console.log(`  - JS:   ${paths.js}`);
console.log(`  - PHP:  ${paths.php}`);
console.log(`  - PO:   ${paths.po}`);


// Helper to run npm scripts
function runNpmScript(script) {
  console.log(`Running: npm run ${script}`);
  const child = exec(`npm run ${script}`);
  child.stdout.pipe(process.stdout);
  child.stderr.pipe(process.stderr);
  child.on('close', (code) => {
    if (code !== 0) {
      console.error(`npm run ${script} exited with code ${code}`);
    }
  });
}

// Initial build
runNpmScript('build');

// Watchers
chokidar.watch(paths.scss, { ignoreInitial: true }).on('all', (event, path) => {
  console.log(`SCSS change detected: ${event} ${path}`);
  runNpmScript('build'); // Re-run full build for SCSS changes
});

chokidar.watch(paths.js, { ignoreInitial: true }).on('all', (event, path) => {
  console.log(`JS change detected: ${event} ${path}`);
  runNpmScript('build'); // Re-run full build for JS changes
});

// For languages, we need to run the specific language build script
chokidar.watch(paths.php, { ignoreInitial: true }).on('all', (event, path) => {
  console.log(`PHP change detected: ${event} ${path}`);
  runNpmScript('languages'); // Only run language script for PHP changes
});

chokidar.watch(paths.po, { ignoreInitial: true }).on('all', (event, path) => {
  console.log(`.po file change detected: ${event} ${path}`);
  runNpmScript('languages'); // Only run language script for .po changes
});
