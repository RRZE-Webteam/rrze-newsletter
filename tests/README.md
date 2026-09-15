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

Keep these stubs limited to small, deterministic boundaries. Contract tests can
assert the arguments supplied to WordPress and the plugin's decisions given
scripted responses. Actual hook dispatch, post storage, permission resolution,
REST routing, taxonomies and multisite belong in the future integration suite
and should run against a real WordPress test installation.

### Newsletter, queue and REST contracts

Post-type tests cover registration arguments, metadata types and defaults,
conditional direct-recipient fields, taxonomy visibility and capabilities,
initial template/status writes, send-data assembly and admin status/actions.
REST tests cover declared permission callbacks, capability decisions, metadata
allow lists, requested writes, sender/recipient feedback, recurrence labels,
author payloads, palette merging and unsaved editor previews. Preview sending
must stop when rendered HTML is missing.

`ApplicationEnvironment.php` records calls and supplies in-memory fixtures. Its
minimal `WP_Post`, `WP_Error` and `WP_REST_Server` aliases are value-object and
constant doubles, not WordPress implementations. This bootstrap is exclusively
for standalone unit tests; do not load it into a real WordPress integration
suite. REST handlers are called directly, so these tests do not verify route
matching, HTTP dispatch, request sanitization, response wrapping or actual
authorization enforcement. Capability mapping and persistence remain WordPress
integration concerns. `ApplicationTestCase` resets fixture state and restores
the renderer's static configuration between tests.

### Calendar formatting and rendering

Calendar tests cover 12/24-hour formats, meridiem localization, date offsets,
daylight-saving transitions, feed timezone fallback, localized date names,
empty/media content, labels, organizers and description options. Prepared event
arrays exercise date-range filtering, duplicate UID suppression, item limits
and style attributes through a test-only subclass of the real calendar block.

`CalendarEnvironment.php` records presentation calls; typography and trimming
are scripted boundaries rather than implementations of WordPress formatting.
Locale fixtures, server-name state and renderer configuration are isolated and
restored. These tests neither fetch nor parse ICS feeds, and do not establish
WordPress URL sanitization, real locale behavior or email-client compatibility.

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
run the real list sanitizers; they do not verify actual transient expiry or
database persistence.
Configuration, recipient and storage fixture state is reset around these tests.

`SubscriptionRequestTest` additionally invokes the real public request handler
with isolated GET/POST fixtures. It covers inactive/wrong-page guards, nonce
rejection, initial signup staging, field errors, confirmation-token consumption
and replay, membership updates, unsubscribe requests and management notices.
The real templates and parser generate forms and notices. Confirmation tests
check message-token replacement, one-day token TTL, invalid email rejection and
token cleanup on failed delivery or a thrown transport exception. Regression
tests assert that failed-send cleanup preserves unrelated transients and that
successful delivery retains its confirmation token. `Send` tests separately assert
success/error return values and the fixed header allow list.

`RequestEnvironment.php` records transient writes and throws a test-only
`RedirectRecorded` exception at `wp_redirect()`, before the following `exit`.
Thus redirects are asserted, but HTTP headers, termination, browser behavior,
nonce cryptography and real request authorization are not tested. Its nonce
field fixture both echoes and returns markup, matching WordPress's default call
shape. Mail passes through the real `Send`/`SMTP` classes to the non-networked
`wp_mail` boundary. Superglobals, mail spies and fixture storage are reset after
each test. This is application logic under scripted WordPress responses, not
a WordPress integration test.

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

`QueueCreationTest` exercises the public `set()` method with the real newsletter
data assembly, tag processing, parser and HTML-to-text converter. Cases cover
duplicate recipients, global/local unsubscribe filtering, direct-recipient
domain restrictions, missing bodies/recipients, skip-and-reschedule behavior,
personalized content, taxonomy count requests and failed insert/content writes.
The previous send date must survive skipped/error attempts. Creating queue rows
must not immediately send mail. Tag tests check names, dates, supported keys and
encrypted subscription links with plain and pretty permalinks.

`QueueCreationEnvironment.php` records insertion and taxonomy calls. Existing
mail-queue metadata fixtures record mail-layer writes separately from the
application's newsletter metadata fixtures. The tests assert these boundary
calls, not WordPress query filtering, persistence, rollback or cron execution.
End-to-end queue creation and unsubscribe enforcement, SMTP integration and
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

Top-level renderer tests assemble the real newsletter template with scripted,
already-parsed blocks. They cover block order, unsupported-block skipping,
reusable group references, preview/background settings, palette/font selection,
stored HTML retrieval and missing-HTML errors. Link tests verify requested UTM
parameters, repeated links and preservation of personalization placeholders.
Their query-string adapter uses ordinary fixture URLs; it is not WordPress URL
validation. No production content or database is loaded.

Real Gutenberg parsing, WordPress theme-palette resolution, HTML attribute
escaping and the complete newsletter-to-email pipeline remain integration-test
work. These tests do not establish that generated emails render correctly in
mail clients.

### Feed placeholders, RSS and archive output

Feed-placeholder tests cover per-feed keys, attribute updates without erasing
other feeds, separate RSS/ICS storage, link color and text overrides. RSS tests
load the bundled block metadata and supply already-parsed feed items to a
test-only rendering harness. Assertions cover the inclusive last-send boundary,
empty titles/dates/links, independent display options, styles, excerpt-length
arguments and ellipsis normalization. The feed fixture records item-limit
requests; it does not implement or test SimplePie's item selection. Typography
and trimming results are scripted. No RSS HTTP fetch or feed parsing occurs.

Archive tests check stored base64 and legacy raw bodies, removal of archive
links while retaining article links, preview tag replacement, missing rendered
HTML and unrelated/missing-post routes. Successful archive rendering is called
through protected-method harnesses, not the output-and-exit request path.
Deprecated archive formats, feed substitution during delivery and actual
WordPress routing still need coverage.

### Editor, layouts and patterns

Editor contract tests cover singleton hook registration, newsletter-only block
restrictions, selective asset-callback removal, email-oriented theme settings,
palette consistency, asset metadata/localization and requested excerpt lengths.
`EditorEnvironment.php` records requested changes rather than implementing
WordPress hook dispatch or theme-JSON merging. A narrow, single-argument closure
fixture exercises excerpt-filter registration, callback identity and priority,
replacement, cleanup and preservation of unrelated filters. It is not a full
WordPress hook implementation or a REST query lifecycle test. The editor
singleton, excerpt state and global hook fixture are restored after each test.

Layout/pattern tests load the real bundled JSON files, check registration scope,
layout metadata, unique IDs/titles, site-name/logo substitution and relative URL
replacement. Asset assertions compare against the bundled build manifests.
These are not Gutenberg parsing, JavaScript execution or browser layout tests.

### Follow-up defects identified during the 80% milestone

- Confirmation cleanup — fixed: `Send::email()` returns a `WP_Error` on ordinary
  transport failure, but `Subscription::sendConfirmation()` previously checked
  only for `false`. It now recognizes both failure results. The regression test
  was verified failing before the fix, then passing afterward; it exercises the
  real `Send`/`SMTP` chain with a non-networked `wp_mail()` failure. A companion
  test verifies that successful delivery retains the token.
- Excerpt cleanup — fixed: `Editor::filterExcerptLength()` previously stored
  `add_filter()`'s boolean return value instead of the registered closure. It now
  retains the closure, removes an existing callback before replacement and clears
  the reference after cleanup. Regression tests were verified failing before the
  fix and passing afterward. They check restored excerpt lengths, unrelated
  callbacks, repeated registration, zero-length excerpts, invalid input and
  repeated cleanup.

Both fixes were handled separately from the coverage expansion.

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

Local milestone (2026-09-15): **80.26% line coverage (5,217 / 6,500)** across the
unchanged `includes` scope, up from 61.02%, with **417 tests and 2,596 assertions**
passing in random order (seed `9152080`). This run used PHP 8.5.10, PCOV 1.0.12 and the
locally available PHPUnit 13.3.3; the Composer development requirement remains
PHPUnit 9.6. Exact executable-line totals can differ with runtime/instrumentation.
Coverage measures executed plugin lines, not branch completeness or successful
integration with WordPress and external services.
