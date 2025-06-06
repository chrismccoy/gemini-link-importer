const fs = require('fs');
const path = require('path');
const { Readable } = require('stream'); // Needed for creating a stream
const through = require('through2'); // Needed by gulp-wp-pot and our custom stream
const wpPot = require('gulp-wp-pot'); // The library itself

// Define paths
const rootDir = path.resolve(__dirname, '..');
const languagesDir = path.join(rootDir, 'languages');
const potFile = path.join(languagesDir, 'gemini-link-importer.po');
const textDomain = 'gemini-link-importer';

async function generatePotFile() {
  console.log('--- Starting .po Generation Process (Pure Node.js) ---');

  if (!fs.existsSync(languagesDir)) {
    fs.mkdirSync(languagesDir, { recursive: true });
  }

  return new Promise((resolve, reject) => {
    // Create a dummy stream that will contain the list of PHP files to process
    // gulp-wp-pot expects a stream of Vinyl files, so we simulate that.
    const fileStream = new Readable({ objectMode: true });

    // Find all PHP files
    const glob = require('glob'); // Require here to avoid top-level issues
    const phpFiles = glob.sync(path.join(rootDir, '**/*.php'), {
      ignore: [
        path.join(rootDir, 'node_modules', '**'),
        path.join(rootDir, 'vendor', '**'),
        path.join(rootDir, 'assets', '**'),
        path.join(rootDir, 'tests', '**'),
        path.join(rootDir, 'bin', '**'),
        path.join(rootDir, 'scripts', '**'),
      ],
    });

    if (phpFiles.length === 0) {
      console.log('No PHP files found for POT generation.');
      fs.writeFileSync(potFile, ''); // Create an empty POT file to prevent errors down the line
      return resolve();
    }

    // Push each PHP file into the stream as a Vinyl-like object
    phpFiles.forEach(filePath => {
      fileStream.push({
        cwd: rootDir,
        base: rootDir,
        path: filePath,
        contents: fs.readFileSync(filePath),
        isNull: () => false,
        isBuffer: () => true,
        isStream: () => false,
      });
    });
    fileStream.push(null); // Signal end of stream

    // Pipe the simulated file stream through gulp-wp-pot's transformation
    fileStream
      .pipe(wpPot({
        domain: textDomain,
        package: 'Gemini Link Importer', // Plugin Name
        bugReport: 'YOUR_BUG_REPORT_URL_OR_EMAIL',
        lastTranslator: 'Your Name <your.email@example.com>',
        team: 'English <your.email@example.com>',
        headers: {
          'Language': 'en_US',
          'Plural-Forms': 'nplurals=2; plural=(n != 1);',
        },
      }))
      .pipe(
        through.obj(function (file, enc, cb) {
          // This "through" stream captures the output Vinyl file from gulp-wp-pot
          if (file.isNull()) {
            return cb(null, file);
          }
          fs.writeFileSync(potFile, file.contents);
          console.log(`Successfully generated: ${path.basename(potFile)}`);
          cb(null, file); // Pass the file along (though we're done with it)
        })
      )
      .on('end', resolve) // Resolve the promise when the stream ends
      .on('error', reject); // Reject if any error in the stream
  });
}

generatePotFile();
