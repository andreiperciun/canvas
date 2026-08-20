# AGENTS.md

Instructions for AI coding agents (GitHub Copilot, Cursor, Claude Code, etc.) working in this
repository. Copilot/Cursor/Claude config files should point here instead of duplicating rules.

## Project

Drupal 11 site with the **Canvas** module, running locally on **Docksal**.

| Item        | Value                                                 |
| ----------- | ----------------------------------------------------- |
| Drupal core | 11.4.5 (pinned exactly via `drupal/core-recommended`) |
| PHP         | 8.4 (`docksal/cli:php8.4-3`)                          |
| Web server  | Nginx (`docksal/nginx`), `NGINX_VHOST_PRESET=drupal`  |
| Database    | MariaDB 10.11                                         |
| Drush       | 13.x (`vendor/bin/drush`)                             |
| Canvas      | `drupal/canvas` ^1.10.1                               |
| Local URL   | http://canvas.docksal.site                            |

## Repository layout

```
composer.json          Single source of truth for all PHP dependencies
composer.lock          Committed. Never hand-edit.
docroot/               Drupal web root (relocated from the default "web/")
  core/                Composer-managed, gitignored
  modules/contrib/     Composer-managed, gitignored
  modules/custom/      Custom modules live here
  themes/custom/       Custom themes live here
  sites/default/       settings.php + settings.docksal.php
config/sync/           Exported configuration, deliberately outside the docroot
.docksal/              Docksal stack: docksal.env, docksal.yml, etc/php/php.ini, commands/init
phpcs.xml.dist         Drupal + DrupalPractice coding standards
```

Rules:

- The web root is `docroot/`, **not** `web/`. Never introduce `web/` paths.
- Configuration sync directory is `../config/sync` (repo root `config/sync`), set in
  `docroot/sites/default/settings.php`.
- Everything under `docroot/core`, `docroot/modules/contrib`, `docroot/themes/contrib`,
  `docroot/profiles/contrib`, `docroot/libraries` and `vendor/` is Composer-managed and gitignored.
  **Never patch contrib or core files in place** — use a Composer patch instead.

## Commands — always run through Docksal

Do not run `php`, `composer` or `drush` against the host. Everything runs inside the containers.

```bash
fin init                      # First-time setup: start, composer install, site install, enable Canvas
fin project start | stop      # Start/stop the containers
fin composer <args>           # Composer inside the cli container
fin drush <args>              # Drush inside the cli container
fin bash                      # Shell inside the cli container
fin db cli                    # MySQL shell
fin logs -f                   # Container logs
```

Admin credentials after `fin init`: `admin` / `admin`. Use `fin drush uli` for a login link.

### Dependencies

```bash
fin composer require drupal/<module>:^1.0     # Add a contrib module
fin composer require --dev <package>          # Add a dev tool
fin drush en <module> -y                      # Enable it
```

Core stays pinned to `11.4.5`; do not run an unscoped `composer update` — update single packages
with `fin composer update drupal/<package> --with-dependencies`.

### Configuration workflow

```bash
fin drush config:export -y     # After any change made through the UI
fin drush config:import -y     # After pulling someone else's config
fin drush config:status        # Check for drift
```

Config changes must be committed as YAML in `config/sync`. Never edit `config/sync` by hand unless
you also verify the change imports cleanly.

## Coding standards & sniffers

Tooling installed via Composer: `drupal/coder`, `squizlabs/php_codesniffer`,
`dealerdirect/phpcodesniffer-composer-installer`, `drupal/core-dev`.

```bash
fin exec vendor/bin/phpcs                       # Sniff custom code (uses phpcs.xml.dist)
fin exec vendor/bin/phpcbf                      # Auto-fix what is fixable
fin exec vendor/bin/phpcs docroot/modules/custom/my_module   # Sniff a single path
fin exec vendor/bin/phpunit -c docroot/core docroot/modules/custom/my_module   # Tests
```

`phpcs.xml.dist` applies the `Drupal` and `DrupalPractice` standards to `docroot/modules/custom`,
`docroot/themes/custom` and `docroot/profiles/custom` for extensions
`php,module,inc,install,test,profile,theme,engine,css,js,yml,twig`.

**Any code you write must pass `phpcs` before you consider the task done.**

### Drupal coding rules to follow when writing code

- 2 spaces for indentation, no tabs. Unix line endings. No trailing whitespace. Files end with a
  single newline. No closing `?>` in PHP files.
- Every file starts with a `@file` docblock; every class, method, function and property has a
  docblock with a one-line summary in the third person ("Returns the...", not "Return the...").
- Control structures: `if ($a) {` — space after the keyword, brace on the same line, always use
  braces even for single statements.
- Constants `TRUE`, `FALSE`, `NULL` are uppercase.
- Naming: classes `UpperCamelCase`, methods/functions/variables `lowerCamelCase` inside classes,
  procedural functions and hooks `module_name_hook_name()` in `snake_case`, constants
  `MODULE_NAME_CONSTANT`.
- Use `use` statements for imports, one class per file, PSR-4 under `src/`.
- Prefer dependency injection (services, `create()` on plugins/controllers) over `\Drupal::` static
  calls. `\Drupal::` is only acceptable in procedural hooks.
- Never build SQL by string concatenation — use the database API with placeholders, or better, the
  entity query API.
- Never output raw user input. Use Twig auto-escaping, `Html::escape()`, `Xss::filter()`, and
  render arrays instead of raw HTML strings. Use `#markup`/`#plain_text` correctly.
- Use `t()` / `$this->t()` for all user-facing strings; never concatenate translated strings, use
  placeholders (`@var`, `%var`, `:url`).
- Use the `StringTranslationTrait`, `MessengerTrait` and `LoggerChannelTrait` rather than global
  functions where a class context exists.
- Respect the entity/config API — no direct writes to config tables, use
  `\Drupal::configFactory()` / injected `ConfigFactoryInterface`.
- Do not use deprecated APIs.
- Twig: no PHP logic in templates, use `|t`, `|clean_class`, and preprocess functions for data prep.
- YAML files (`*.info.yml`, `*.routing.yml`, `*.services.yml`, `*.permissions.yml`) use 2-space
  indentation and must declare `core_version_requirement: ^11` in `.info.yml`.

## Canvas

- Canvas is enabled by `fin init`. It is a page/experience building UI; do not modify anything
  under `docroot/modules/contrib/canvas`.
- Canvas is asset-heavy: PHP `memory_limit` is raised to 512M in `.docksal/etc/php/php.ini`. If you
  hit memory errors, raise it there rather than with `ini_set()`.
- After enabling or updating Canvas, always run `fin drush cr` and `fin drush config:export -y`.

## Things not to do

- Do not commit secrets. `settings.docksal.php` is local-only credentials and is intentionally
  committed; real credentials belong in `settings.local.php` (gitignored).
- Do not change the web root, the config sync path, or the pinned core version without being asked.
- Do not add a dependency when Drupal core or the standard library already provides the feature.
- Do not create documentation files unless asked.
