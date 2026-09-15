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

### Recurrence boundaries

The recurrence suite checks setter validation, numeric/weekday limits, cloned
dates, string exclusions, RRULE date parsing, time filters, intervals across all
frequencies, week starts, strict-mode failures and empty/exhausted search windows.
Expected dates are explicit and fixtures use UTC unless testing another timezone.

Known gap found while extending these tests: daily generation omits an occurrence
exactly equal to `UNTIL`, although `occursOn()` accepts that time. For example,
`DTSTART=20260102T090000Z;FREQ=DAILY;UNTIL=20260104T090000Z` generates January 2
and 3, not January 4. Exact end equality also affects `getOccurrencesBetween()`.
This needs a separate production fix and a regression assertion for the final
occurrence; the new passing generation tests use an end one second after it.

### Settings and subscription policies

Settings tests cover default merging, unknown saved-key removal, queue-limit
filters, required-field and sanitizer failures, preservation of other settings,
tab selection, and text/number/checkbox/radio/select/page/textarea/password fields.
`SettingsEnvironment.php` supplies a controlled field schema, not the production
`config/settings.php`. The harness bypasses hook registration; serialization,
selection helpers and page dropdowns are small deterministic stubs. Form
assertions check structure and values, not browser behavior or WordPress escaping.

Subscription tests cover normalized list IDs, allowed data keys, encrypted query
decoding, one-time transient consumption, membership display, global/per-list
unsubscribe handling, resubscription and preservation of existing member names.
`SubscriptionEnvironment.php` records term queries and option/meta writes in
memory. These tests invoke protected policies through a test-only subclass and
run the real list sanitizers; they do not exercise the public request handler,
confirmation emails, permissions, actual transient expiry or database persistence.
Configuration, recipient and storage fixture state is reset around these tests.

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

### Recipient and validation helpers

Recipient tests cover email shapes, independent sender/recipient allow lists,
exact-domain restrictions, failure logging, mailing-list field parsing, sorting,
last-duplicate-wins behavior, optional names, and unsubscribe-list cleanup.
Both text and associative-array outputs are checked, including empty lists and
mixed valid/invalid rows. The current parser is comma-delimited, not a general
quoted-CSV parser; columns after the last name are ignored.

`tests/Support/RecipientEnvironment.php` supplies scripted sanitization results,
domain-filter values and recorded log calls. Default fixtures are already
sanitized. These stubs do not validate WordPress sanitization, filter dispatch,
log delivery or persisted unsubscribe enforcement. State is reset before and
after each recipient test.

Pure utility tests additionally check inclusive integer bounds, date/time
overflow rejection, leap years, exact/custom date formats, and recursive key
search ordering and value preservation. Integer-range cases use integer text;
they do not establish strict validation of arbitrary numeric input.

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

Social-link tests cover supported service colors, filled/circle styles, the
default feed variant, and the presence of both bundled icon variants for every
supported service. Processor tests check element order, skipped incomplete or
unsupported links, parent styles, container defaults, URL serialization and
root/column wrapping. A namespaced `plugins_url()` stub supplies deterministic
asset URLs; WordPress URL filters, URL-scheme validation and actual asset serving
are not exercised.

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

### Template assembly

Template tests load the real `includes/templates/newsletter.mjml` file through
`Templates` and the real parser. They check missing files, raw loading without
data, interpolation, output-buffer isolation, title/preview/body assembly,
layout attributes, selected CSS rules, whitespace compaction and hover-rule
removal. Personalization tokens inserted with the body must survive for later
per-recipient parsing; consecutive renders must not reuse earlier content.

These fixtures use trusted, XML-compatible content. DOM assertions verify
structure, not sanitization, complete CSS/MJML validity, MJML-to-HTML compilation
or rendering in email clients. The plugin-directory stub points only to this
repository; tests neither alter templates nor load live WordPress data.

## Code coverage

PCOV must be installed and loadable by PHP. The Composer command enables it
for the test process and reports coverage for the plugin code in `includes`:

```shell
composer test:coverage
```

Generated files, dependencies and WordPress itself are intentionally excluded
from the coverage scope.
