const fs = require('fs');
const path = require('path');
const glob = require('glob');

// Define paths
const rootDir = path.resolve(__dirname, '..');
const languagesDir = path.join(rootDir, 'languages');

// Main async function to compile .po to .mo
async function compileMoFiles() {
  console.log('--- Starting MO Compilation Process ---');

  // Dynamically import gettext-parser within the async function
  const gettextParser = await import('gettext-parser');

  let compiledCount = 0;
  const poFiles = glob.sync(path.join(languagesDir, '*.po'));

  if (poFiles.length === 0) {
    console.log("No .po files found to compile.");
  } else {
    for (const poFilePath of poFiles) {
      const moFilePath = poFilePath.replace(/\.po$/, '.mo');
      try {
        const poContent = fs.readFileSync(poFilePath);
        const poData = gettextParser.po.parse(poContent);
        const moBuffer = gettextParser.mo.compile(poData);
        fs.writeFileSync(moFilePath, moBuffer);
        console.log(`  Compiled ${path.basename(poFilePath)} -> ${path.basename(moFilePath)}`);
        compiledCount++;
      } catch (error) {
        console.error(`Error compiling ${path.basename(poFilePath)}: ${error.message}`);
      }
    }
    if (compiledCount > 0) {
      console.log(`Successfully compiled ${compiledCount} .po file(s).`);
    }
  }

  console.log('--- MO Compilation Process Completed Successfully ---');
}

// Execute the main async function
compileMoFiles();
