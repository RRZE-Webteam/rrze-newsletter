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
npm run test
```

Keep these stubs limited to small, deterministic units. Behaviour that depends
on WordPress hooks, post storage, permissions, REST routing, taxonomies or
multisite belongs in the future integration suite and should run against a
real WordPress test installation.

### Mail queue tests

Queue tests cover message forwarding and headers, legacy unencoded HTML,
successful delivery, retry boundaries, independent recipient failures, missing
or wrong-type newsletters, and the exact one-minute processing cutoff.
`tests/Support/QueueEnvironment.php` supplies a deterministic clock and small
in-memory cache/option boundaries. The fake SMTP transport never sends mail.
State is reset before and after each queue test.

Policy tests expose the queue's protected skip and rescheduling methods through
a test-only subclass. They exercise RSS/calendar condition combinations,
per-newsletter cache cleanup, recurrence guards, hourly/daily/weekly/monthly
dates, daylight-saving conversion and the five-minute fallback. Real `Utils`
and `Recurrence` code calculates the next occurrences. Weekly and monthly queue
rules currently start from date-only values and therefore schedule at midnight;
the tests explicitly characterize that behavior.

These tests assert database query arguments and requested writes, not real
WordPress query filtering, persistence or cron execution. End-to-end queue
creation, recipient deduplication/unsubscribe handling, SMTP integration and
protection against concurrent duplicate sends still need integration coverage.

### SMTP and encrypted-value compatibility tests

SMTP tests bypass construction to inject explicit settings, then exercise the
real send, configuration and callback methods. They check message forwarding,
sender fallbacks, TLS/SSL/authentication options, password decoding, embedded
image selection, consecutive-message state and cleanup after both `true` and
`false` transport results. Existing callbacks belonging to other callers must
remain registered.

`tests/Support/MailEnvironment.php` records hook operations and replaces
`wp_mail()` with a non-networked test boundary. Tests explicitly invoke callbacks
against a configuration spy; this is not WordPress hook dispatch or PHPMailer.
The support state is reset before and after every SMTP test. Real settings
loading, hook priorities, transport exceptions and actual delivery still need
integration tests.

Password and URL-token tests use fixed ciphertext fixtures, Unicode/whitespace
round trips, padding cases and malformed inputs. Synthetic namespace-local
`AUTH_KEY` and `AUTH_SALT` constants keep these tests independent of live site
credentials. They characterize the existing storage/link format, not its
cryptographic strength, authentication or tamper resistance. An intentional
format migration will need corresponding compatibility-test updates.

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
columns and grid cells. Image rendering fixtures provide dimensions directly, so these
tests never fetch external images or query WordPress attachments.

Image-size resolver tests separately cover attachment precedence, local path
checks, real image decoding, remote-response validation, the two-request limit,
positive and negative caching, and cache resets between renders. Their
namespaced WordPress stubs live in `tests/Support/ImageLookupEnvironment.php`.
The bootstrap points `WP_CONTENT_DIR` at the repository's read-only `assets`
fixtures; no live WordPress files, database or network are used. Each test resets
the resolver and stub state. Cache TTL arguments are checked, but actual
WordPress transient expiration and SSRF protection are not simulated.

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
validation, image URL normalization and the real attachment, transient and HTTP
APIs still need integration coverage. Image tests restore the libxml error mode and reset
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
