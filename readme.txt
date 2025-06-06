=== Gemini Link Importer ===
Contributors: google
Donate link: https://example.com/donate (optional)
Tags: links, import, link manager, bulk import, csv, ajax, i18n
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: gemini-link-importer
Domain Path: /languages

A robust, modern WordPress plugin to import links into the Link Manager from a textarea using a simple, flexible format.

== Description ==

Gemini Link Importer provides a user-friendly admin interface to quickly import multiple links into the WordPress Link Manager. Users can paste links in a `URL, Title, Category` format, one link per line.

This plugin is built with a modern development workflow, leveraging pure Node.js scripts for asset compilation and internationalization, along with PHPUnit for testing.

**Key Features:**

*   **Simple Import Interface:** A single textarea for pasting links.
*   **Flexible Format:** Supports `URL, Title, Category`.
    *   Title defaults to URL if omitted.
    *   Category defaults to "Uncategorized" if omitted.
    *   Fields can be optionally enclosed in double quotes (e.g., `"URL", "Title", "Category"`).
*   **Automatic Category Creation:** If a specified link category doesn't exist, it's created automatically.
*   **AJAX Powered:** Smooth import process without page reloads, including a loading spinner and disabled button state.
*   **Duplicate Link Prevention:** Checks for existing links with the same URL within the *same category* before importing.
*   **Detailed Feedback:** Provides clear success, failure, and informational messages after each import attempt, including counts and lists.
*   **Robust Dependency Check:** Correctly detects if the Link Manager functionality is enabled, whether by the official plugin or a theme's `functions.php` filter.
*   **Internationalization Ready:** Fully translatable with a provided English `.po` source file.

== Installation ==

1.  **Upload Plugin:**
    *   In your WordPress admin, go to `Plugins > Add New`.
    *   Click `Upload Plugin` and choose the `gemini-link-importer.zip` file.
    *   Click `Install Now`.
    *   OR, extract the zip file and upload the `gemini-link-importer` folder to the `/wp-content/plugins/` directory.
2.  **Activate Plugin:** Activate "Gemini Link Importer" through the 'Plugins' menu in WordPress.
3.  **Ensure Link Manager is Active:**
    *   This plugin requires the "Link Manager" functionality. If it's not active, you will see a notice prompting you to enable it.
    *   You can enable this by installing and activating the official "Link Manager" plugin from the WordPress.org repository, or by adding `add_filter( 'pre_option_link_manager_enabled', '__return_true' );` to your theme's `functions.php` file.
4.  **Access Importer:** Once active, navigate to `Links > Import Links` in your WordPress admin menu.
5.  **Import:** Paste your links into the textarea and click "Import Links".

== Frequently Asked Questions ==

= What is the format for importing links? =

Each link should be on a new line in the textarea, using a comma-separated format:
`URL,Link Name,Link Category`

Fields can also be enclosed in double quotes, for example:
`"https://example.com/page","My Page Title","My Custom Category"`

= What happens if I specify a category that doesn't exist? =

The plugin will automatically create the link category for you.

= What if I try to import a link that already exists? =

The plugin checks for duplicate links based on the URL **within the specified category**. If an identical URL is found in the same category, that specific link will not be imported, and an error message will be shown for it.

== Screenshots ==

1.  The Gemini Link Importer admin interface, showing the textarea for input and the import button.
2.  Example of feedback messages after a mixed import, showing successful, failed, and new category results with counts.
3.  The plugin's admin menu item under the main "Links" menu.

== Changelog ==

= 1.0.0 =
*   Initial release.
*   Feature: Import links via textarea with `URL, Title, Category` format.
*   Feature: AJAX-powered import with detailed feedback and counts.
*   Feature: Automatic category creation.
*   Feature: Duplicate link checking (URL + Category specific).
*   Feature: Support for optionally quoted fields.
*   Feature: Robust Link Manager activation check (works with plugins and theme filters).
*   Feature: Full internationalization support with a `languages` directory and source `.po` file.
*   Feature: Modern development workflow leveraging pure Node.js scripts for asset compilation and language file generation.
*   Feature: Includes a full PHPUnit test suite for core functionality.

== Upgrade Notice ==

= 1.0.0 =
Initial release of the plugin.

== Developer Documentation ==

This plugin is built with a modern, testable, and maintainable workflow leveraging pure Node.js scripts.

**Development Stack:**
*   **PHP Linting:** PHPCS with the `WordPress` ruleset.
*   **Unit Testing:** PHPUnit with the WordPress test suite.
*   **Build System:** Pure Node.js scripts (no Gulp/Webpack).
*   **CSS Pre-processing:** Sass (directly via `sass` Node.js package).
*   **JS Linting:** JSHint.
*   **JS Minification:** `terser`.
*   **CSS Post-processing/Minification:** `postcss` with `autoprefixer` and `cssnano`.
*   **File Watching:** `chokidar`.
*   **Language File Generation:** `gettext-extractor` (for `.po` source generation) and `gettext-parser` (for `.mo` compilation).

**Initial Setup:**
1.  Clone the repository.
2.  Run `npm install` to install Node.js dependencies.
3.  Run `composer install` to install PHP dependencies (PHPUnit, PHPCS).
4.  Set up the WordPress test environment: `composer run-script install-wp-tests`.

**Build Commands:**
*   `npm run build`: Compiles all assets (CSS, JS) and generates/compiles language files.
*   `npm run start` or `npm run watch`: Watches for changes in SCSS, JS, and PHP files to automatically recompile assets and update language files.

**Linting & Testing:**
*   `composer lint` (or `vendor/bin/phpcs`): Lints PHP files.
*   `npx jshint assets/js/admin.js`: Lints the source JavaScript file.
*   `composer test` (or `vendor/bin/phpunit`): Runs the PHPUnit test suite.
