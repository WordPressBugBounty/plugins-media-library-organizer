# Agentic workflow

## Project overview

Media Library Organizer is a WordPress plugin for organizing media attachments into folders and categories. Its entry point is `media-library-organizer.php`, which defines plugin constants, registers activation hooks, loads Composer dependencies when available, and starts the `Media_Library_Organizer` singleton.

## Build and development commands

```bash
# Install JavaScript and PHP dependencies
npm ci && composer install

# Build JavaScript and SCSS assets
npm run build

# Watch JavaScript and SCSS assets during development
npm run start

# PHP quality checks
composer lint
composer lint-test
composer phpstan

# PHPUnit requires MySQL and the WordPress test suite. Install the suite once.
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1
composer phpunit

# Browser tests require Docker.
npm run wp-env start
npm run test:e2e
```

`tests/bootstrap.php` reads `WP_TESTS_DIR`; when it is unset, it expects the WordPress test suite in the system temporary directory under `wordpress-tests-lib`.

## Architecture

- `media-library-organizer.php` — plugin bootstrap and the custom class autoloader.
- `includes/` — core PHP logic. `includes/global/` holds frontend and shared behavior; `includes/admin/` holds administration screens, AJAX, REST endpoints, imports, exports, and CLI integration.
- `addons/` — optional feature modules: `defaults`, `output`, and `tree-view`.
- `_modules/dashboard/` — shared dashboard UI module.
- `views/` — PHP templates for admin and global views.
- `assets/` — source JavaScript, SCSS, images, and compiled assets. `assets/src/` contains the modern block, settings, and sidebar code.
- `tests/` — PHPUnit tests and Playwright E2E setup. `.wp-env.json` runs the plugin against WordPress with PHP 7.4.
- `bin/dist.sh` — distribution packaging.

## Asset workflow

`webpack.assets.config.js` discovers root and add-on JavaScript and SCSS files. It writes compiled CSS alongside its source paths and minified JavaScript under `assets/js/min/`, `_modules/**/js/min/`, and equivalent add-on paths. Run `npm run build` after changing source assets; do not hand-edit generated minified files.

## Coding conventions

- Follow the project’s WordPress Extra and WordPress Docs PHPCS rules in `phpcs.xml`.
- Use tabs for PHP indentation and the `media-library-organizer` text domain for translatable strings.
- The plugin supports WordPress 5.0 and PHP 7.4 in its local E2E environment. Keep compatibility in mind when choosing PHP and JavaScript syntax.
- PHP class names follow the `Media_Library_Organizer_*` pattern. The bootstrap autoloader maps them to lowercase `class-media-library-organizer-*.php` files under `includes/admin/` or `includes/global/`.
- Keep changes to add-on behavior within the relevant `addons/<name>/` module when possible.

## Testing guidance

Run the narrowest relevant check first. For PHP changes, use `composer lint` and the affected PHPUnit tests; use `composer phpstan` when the change affects typed PHP behavior. For admin UI, block, or asset changes, build assets and run the relevant Playwright test when available.

## Releases

Semantic Release runs through `npm run release`. The Grunt version task keeps versions synchronized across `package.json`, `readme.txt`, and `media-library-organizer.php`. Use `npm run dist` to produce the distributable plugin package.
