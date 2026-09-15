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

### MJML helper and block processor tests

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

Block processor tests exercise lists, columns, groups, grids, spacers and
separators through their public rendering methods, including recursive calls
through `BlockProcessor`. They verify element nesting, content order, attribute
inheritance, automatic widths, mobile grouping and incomplete grid rows.

Button tests cover HTML and attribute-based labels and link metadata,
typography inheritance, outline styles, borders, padding, alignment and bounded
percentage widths. Image tests cover intrinsic and explicit dimensions,
aspect ratios, size presets, plain captions, links and sizing inside padded
columns and grid cells. Image fixtures provide dimensions directly, so these
tests never fetch external images or query WordPress attachments.

The grid-padding regression checks that a 300px cell with 20px padding on each
side renders its image at 260px, not 220px. Additional cases preserve the
image's own padding and inherited button typography.

`tests/Support/MjmlTestCase.php` provides small already-parsed block fixtures
and DOM/XPath assertions. These fixtures intentionally use XML-compatible HTML;
the assertions check well-formed fragments and selected structural rules, not
the complete MJML schema. Attribute order and insignificant serialization
differences do not affect the structural assertions.

The unit bootstrap also supplies a minimal namespaced `esc_attr()` stub for
fixture serialization. It does not emulate WordPress filters or establish the
correctness of WordPress escaping. Paragraph and heading processing that relies
on `WP_HTML_Tag_Processor` is deferred to tests with the real WordPress API.

Button URL tests use ordinary valid URLs. WordPress's `esc_url_raw()` scheme
validation, image URL normalization and attachment/HTTP dimension lookup still
need integration coverage. Image tests restore the libxml error mode and reset
dimension caches after each test: the current image parser changes libxml's
global mode without restoring it, which remains a separate cleanup task.

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
