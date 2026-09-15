# Tests

The test suite uses PHPUnit 9.6. Install the development dependencies before
running it locally:

```shell
composer install
```

## Unit tests

The fast unit tests live in `tests/Unit` and use the lightweight WordPress
stubs from `tests/bootstrap.php`. They do not require a running WordPress
installation or a database.

```shell
composer test
composer test:unit
```

Keep these stubs limited to small, deterministic units. Behaviour that depends
on WordPress hooks, post storage, permissions, REST routing, taxonomies or
multisite belongs in the future integration suite and should run against a
real WordPress test installation.

### MJML helper tests

Run the rendering helper tests on their own with:

```shell
composer test:unit -- --filter 'Tests\\Unit\\MJML'
```

These tests cover attribute conversion, color and font precedence, spacing
presets used by the bundled layouts, pixel and percentage widths, inheritance
between blocks, and independent render contexts for sibling columns.
Expected values are explicit; no database, network or wall-clock time is used.
The spacing tests use a namespaced stub for WordPress's numeric conversion
function `absint()`.

WordPress theme-palette resolution, HTML attribute escaping and full newsletter
rendering remain integration-test work. These helper tests do not establish
that generated emails render correctly in mail clients.

## Code coverage

PCOV must be installed and loadable by PHP. The Composer command enables it
for the test process and reports coverage for the plugin code in `includes`:

```shell
composer test:coverage
```

Generated files, dependencies and WordPress itself are intentionally excluded
from the coverage scope.
