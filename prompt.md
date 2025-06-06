Develop a WordPress plugin named "Gemini Link Importer" in an OOP style, including type hinting and proper code formatting.

**I. Core Functionality & Admin Interface:**

1.  **Plugin Goal:** Provide an admin interface to import links into the WordPress Link Manager.
2.  **Input Method:** A single textarea where users can paste multiple links, one per line.
3.  **Input Format:**
    *   Each line must support a comma-separated format: `URL, Title, Category`.
    *   This format should be flexible to allow optional double quotes around each field (e.g., `"URL", "Title", "Category"` or `URL,Title,Category` or mixed).
    *   If the `Title` is omitted, the `URL` should be used as the link name.
    *   If the `Category` is omitted, the link should be assigned to the "Uncategorized" (default) category.
    *   If a specified category does not exist, it should be automatically created.
4.  **Admin Page:**
    *   Accessible as a submenu item under the existing "Links" menu in the WordPress admin.
    *   The menu title should be "Import Links".
    *   This requires the "Link Manager" functionality to be active/enabled. The plugin must check for this using `get_option('link_manager_enabled')`. This check must be hooked to the `init` action to correctly respect filters added by themes (e.g., in `functions.php`). An admin notice should be displayed if the functionality is not enabled.

**II. Import Process (AJAX):**

1.  Utilize WordPress AJAX methods for the import process.
2.  Upon submission:
    *   The textarea content should be sent via AJAX to a PHP handler.
    *   The textarea should be cleared after a successful or partially successful submission.
    *   A visual spinner should be displayed, and the submit button should be disabled during the AJAX request, with its text changing to "Importing links...".
    *   After the AJAX request completes, the button should be re-enabled and its text restored.
    *   Perform a client-side check to prevent AJAX submission if the textarea is empty (or contains only whitespace), showing an appropriate info message instead.

**III. Validation and Reporting:**

1.  **URL Validation:** Basic validation for each URL.
2.  **Duplicate Check (Category Specific):**
    *   Before inserting, check if a link with the exact same URL already exists *within the specified category*.
    *   If a duplicate is found, the link should not be imported, and a specific error message should be displayed.
    *   This check should be robust, potentially using WordPress-idiomatic functions like `get_bookmarks()`.
3.  **Real-time Feedback (Admin Notices via JavaScript):**
    *   Display a summary message for overall success, overall failure, or if no valid links were found to process. This summary should include counts where applicable (e.g., "Successfully imported X links," "Import completed with some issues. Successfully imported X links, Y failed, and Z new categories were created.").
    *   List all successfully imported links by their URL only. The heading for this list should include the count (e.g., "Successfully Imported (X):").
    *   List all links that failed to import, including their URL and a specific reason (e.g., "Invalid URL format," "Duplicate link found in category 'Category Name'," "Error creating category," "Error inserting link," "Simulated WP_Error during link insertion," "Simulated term creation failure."). The heading for this list should also include the count (e.g., "Failed to Import (Y):").
    *   Display a separate info message if any new categories were created. The heading for this list should include the total categories created from the current import batch (e.g., "New Categories Created (Z):").
    *   Do not show a "no links failed" message if no links were submitted in the first place (handled by client-side empty check).

**IV. Code Structure and Standards:**

1.  **PHP:**
    *   All PHP files must be namespaced under `GeminiLinkImporter`.
    *   All class names, constants, and global function names must be consistently prefixed with "gemini" (e.g., `GeminiLinkImporter\LinkImporter`, `gemini_link_importer_run`).
    *   The main plugin file should be named `gemini-link-importer.php` and located in a folder also named `gemini-link-importer`.
    *   The primary plugin file (`gemini-link-importer.php`) should explicitly `require_once` its main class files (e.g., `inc/class-admin-page.php`, `inc/class-link-importer.php`) instead of using an `spl_autoload_register` function.
2.  **JavaScript:**
    *   All JavaScript global objects/IDs should be consistently prefixed with "gemini" (e.g., `geminiLinkImporterAjax`).
    *   The source JavaScript file (`assets/js/admin.js`) should include comprehensive `console.log` debugging for every significant step in the process (initialization, form submission, content checks, button states, AJAX parameters, AJAX callbacks, data parsing, notice construction, errors, completion, notice dismissal).
    *   The source file will be minified to `assets/js/admin.min.js`, and the plugin should enqueue this minified version for production use.
3.  **CSS/SCSS:**
    *   Admin styles should be written in SCSS (`assets/scss/admin.scss`) and compiled to both a readable `admin.css` and a minified `admin.min.css`.
    *   The plugin should enqueue the minified `admin.min.css` file for production use.
4.  **Internationalization (i18n) & Localization (l10n):**
    *   Create a `languages` directory in the plugin root.
    *   The main plugin file should include the `Text Domain` (e.g., `gemini-link-importer`) and `Domain Path` (e.g., `/languages`) headers.
    *   Implement a function hooked to `plugins_loaded` to call `load_plugin_textdomain()`.
    *   Ensure all user-facing strings in PHP files are correctly wrapped in WordPress translation functions (e.g., `__()`, `esc_html__()`, `_e()`, `esc_html_e()`, `sprintf()`) using the defined text domain.
    *   JavaScript strings should be localized via `wp_localize_script` using translatable PHP strings.

**V. Development Workflow & Tooling:**

1.  **`package.json`:** Create a `package.json` file to manage Node.js dependencies. Include `autoprefixer`, `chokidar`, `cssnano`, `gettext-parser`, `gettext-extractor` (for .po generation), `glob`, `gulp-wp-pot`, `postcss`, `sass` (pinned to `1.62.1`), `terser`, and `through2`. Include npm scripts for `build`, `start`, `watch`, `lint:php`, `test`, and `languages` (which orchestrates `make-pot` and `compile-mo`).
2.  **Pure Node.js Build Scripts:**
    *   Create `scripts/build.js` for main JS/CSS compilation (Sass to CSS, PostCSS for autoprefixing/cssnano, Terser for JS minification).
    *   Create `scripts/watch.js` for file watching (`chokidar`) and triggering build tasks.
    *   Create `scripts/make-pot.js` to generate the `.po` language file (using `gulp-wp-pot` directly via a Node.js stream). This script must correctly handle the `gulp-wp-pot` API for generating the `.po` file from PHP sources and writing it to the specified path.
    *   Create `scripts/compile-mo.js` to compile `.po` files to `.mo` (using `gettext-parser`). This script must correctly use `gettext-parser` as an ES Module via `await import()`.
3.  **`.gitignore`:** Create a `.gitignore` file to exclude `node_modules/`, OS-generated files, IDE files, and other common non-versioned items. The compiled assets (`admin.min.css`, `admin.min.js`) and language files (`.po`, `.mo`) should *not* be ignored if they are to be distributed with the plugin.
4.  **PHPCS:**
    *   Create a `phpcs.xml` (or `phpcs.xml.dist`) configuration file.
    *   Configure it to use the WordPress Coding Standards (`WordPress` ruleset).
    *   Set the project's text domain (`gemini-link-importer`) and minimum PHP version.
    *   Configure `PrefixAllGlobals` with the "gemini" prefix.
    *   Exclude `node_modules`, `vendor`, compiled CSS, and JS assets from PHP linting.
5.  **JSHint:**
    *   Create a `.jshintrc` file for linting `assets/js/admin.js`.
    *   Configure it for a browser/jQuery environment (ES5), enforce strict mode, warn on undefined/unused variables, and define the `geminiLinkImporterAjax` global.
6.  **Unit Testing (PHPUnit):**
    *   Set up PHPUnit for WordPress plugin testing.
    *   Create a `composer.json` to manage PHPUnit and `yoast/wp-test-utils` (or similar) dependencies. Include scripts for installing the WP test environment and running tests.
    *   Create `bin/install-wp-tests.sh` (within the `bin` directory at the plugin root) to set up the WordPress testing environment.
    *   Create a `phpunit.xml.dist` configuration file, pointing to a `tests/bootstrap.php` file and test directory (`tests/phpunit/`). Configure code coverage for plugin files.
    *   Create `tests/bootstrap.php` to load the WordPress testing environment and the plugin itself.
    *   Develop a comprehensive test suite (`LinkImporterTest.php`) extending `WP_UnitTestCase`:
        *   Test class instantiation and hook registration.
        *   Test permission checks (user capabilities).
        *   Test input validation (missing/empty link data).
        *   Test various line parsing scenarios (full data, defaults, quoted/mixed fields, extra commas, empty/whitespace lines).
        *   Test URL validation failures.
        *   Test category handling (existing, new creation).
        *   Test duplicate link detection (same category vs. different category).
        *   Test batch processing with mixed success/failure results.
        *   Include advanced/complex tests simulating core WordPress function failures (e.g., `wp_insert_term` returning `WP_Error`, `wp_insert_link` returning `WP_Error`) using WordPress filters or other appropriate techniques to verify error handling paths.

**VI. Plugin Documentation:**

1.  **`readme.txt`:** Create a WordPress-standard `readme.txt` file for the plugin, including:
    *   Header block with plugin metadata (contributors, tags, requires, tested up to, stable tag, license, text domain, domain path).
    *   Description, Installation instructions, FAQ, Screenshots (descriptions), Changelog, and a detailed Developer Documentation section explaining the pure Node.js build process.
    *   Ensure it's formatted correctly for the WordPress.org plugin repository.

**VII. Sample Data Generation (for testing):**

1.  Provide a script (e.g., Python) or method to generate a sample CSV file with 100 links.
2.  The CSV should contain the 3 required fields: URL, Title, Category.
3.  Specific distribution:
    *   40 of the links should be duplicates within the *same category* (i.e., 20 unique URLs, each appearing twice in that one category).
    *   The remaining 60 links should each have a *different, unique category name*.
4.  Use random domain names, random titles (related to the domain), and random category names (for the unique ones) to make the data varied.
