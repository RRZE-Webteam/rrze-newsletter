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

`npm run test` runs the PHP suite, compiled MJML/HTML, contrast and editor regressions
described below. It requires the Composer and npm dependencies to be installed.

### Managed spacing and per-newsletter overrides

The opt-in global `design_managed_spacing` setting is overridden by newsletter
meta `rrze_newsletter_spacing_mode`: `inherit` (default), `managed`, or `expert`.
PHP tests cover precedence, invalid/missing values, unchanged manual-mode output,
immutable source blocks, nested groups through twelve levels, grids, columns,
full-bleed root images, spacer normalization and REST response metadata.

Managed output uses 24px outer side gutters (16px below 480px), 16px component
gaps and 8px side padding inside layout columns. Flattened groups add no gutters.
Typography margins/padding are reset during MJML inlining; list indentation and
button inner padding retain usable defaults. This is a spacing preset, not a
general validator for arbitrary HTML, column widths, borders or email designs.

`tests/MJML/spacing.test.cjs` compiles synthetic fixtures with the real compiler.
`npm run test:editor` checks the actual inspector component against small UI/store
boundaries and loads the production settings schema in an isolated PHP process.
Save middleware tests verify the mode is persisted before MJML generation, show
a preview notice for managed output and keep contrast protection independent.
These do not prove real WordPress REST storage or browser interaction.

Editor spacing tests additionally exercise the live mode effect, the selected
block's Dimensions hint, and the DOM presentation controller. The controller
styles only newsletter canvas roots and known editor/post-inserter preview
iframes. Tests cover late mounts, frame loads/replacement (including Gutenberg's
body-less iframe load followed by a React body portal), React root-class
updates, canvas-width breakpoints, cleanup and byte-for-byte preservation of
block markup/inline attributes. jsdom tests verify lifecycle and DOM contracts,
not native CSS layout. No WordPress store writes are used by this presentation.
Mode regression tests also cover the string values `"1"`/`""` produced by
`wp_localize_script`, along with booleans and false-like strings. Inspector tests
use the real mode resolver, so the preview effect, hint and global-setting label
cannot silently diverge at this PHP-to-JavaScript boundary.
The editor CSS approximates the PHP spacing preset; keep its values in sync with
`ManagedSpacing` and the group/column/grid processors. Adjacent spacer blocks
remain individually selectable in the editor, even if output combines them.

`tests/Browser/editor-spacing.test.cjs` checks computed spacing against the built
editor CSS in a native browser, including simultaneous iframe/main canvases,
resizing, nested groups, untouched inspector UI and return to manual spacing.

`npm run test:browser` includes optional headless Chrome checks at 240–680px,
both with and without head styles. Network requests are blocked. Browser tests
are separate from the default suite and do not establish Outlook/Apple Mail/Gmail
compatibility; preview and test-send representative newsletters before rollout.

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

Inclusive `UNTIL` boundary — fixed: generation previously omitted occurrences
exactly equal to `UNTIL`, although `occursOn()` accepted that time. For example,
`DTSTART=20260102T090000Z;FREQ=DAILY;UNTIL=20260104T090000Z` now includes January 4
at 09:00, as well as January 2 and 3. The shared generation loop now includes
equality. Seven regression cases were verified failing before the fix.

Tests cover exact endpoints across all seven frequencies, daily bounds one
second before/after an occurrence, start equal to end, count limits, exclusions,
intervals, UTC end dates across a local daylight-saving transition and matching
endpoints in `getOccurrencesBetween()`. The previous one-second workarounds were
removed. Empty/reversed query windows still return no occurrences.

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
APIs still need integration coverage. Image tests restore the libxml error mode
and reset dimension caches after each test, including when an assertion fails.

Image-parser libxml state leak — fixed: the parser previously enabled internal
error handling without restoring the caller's setting. It now saves the prior
mode and restores it in `finally`, alongside the existing error-buffer cleanup.
Three regression tests were verified failing before the fix and passing after
it. They cover both prior modes with valid images, empty/image-free markup and
malformed markup that produces real libxml diagnostics. The tests check mode
restoration and cleared diagnostics directly, without mocking libxml.

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

### Compiled image HTML regressions

```shell
npm run test:mjml
npm run test
```

`tests/MJML/images.test.cjs` uses Node's built-in test runner and the installed
`mjml-browser` compiler with the editor's compilation options. Its CLI-only PHP
fixture runs the real `ImageProcessor` and `TemplateRenderer` against synthetic
images with known dimensions. Small plugin-path and escaping stubs replace
WordPress boundaries; unexpected image metadata lookups fail immediately. No
WordPress installation, database, browser, image download or email send is used.

Automatic image heights — fixed: the renderer previously calculated a fixed
pixel height which MJML combined with `width:100%`. Images could become distorted
when their container shrank, especially above the template's 479px workaround.
The renderer now emits `height="auto"` for intrinsically sized and width-only
images, while preserving the same width limits. Explicit height requests retain
their existing behavior; this change does not redefine intentional image sizing
or cropping. The existing mobile CSS fallback remains unchanged.

Nine compiled-HTML regression cases were verified failing before the fix. They
cover landscape/portrait images, small images, pixel/percentage/oversized widths,
size presets and a narrow container. Assertions check the image's inline
`height:auto`, fluid width and absence of a fixed HTML height, independently of
head CSS or media-query support. Three control cases preserve explicit sizing.
PHP tests also cover widths on both sides of 479px and padded columns/grid cells.
These are output-contract tests, not visual email-client compatibility tests.

Existing newsletters need to be saved again in the editor to regenerate their
stored email HTML. Already queued or delivered messages are not rewritten.

### Responsive button regressions

Button percentages are now kept as percentages in MJML, with the existing
1–100% limits. Previously a full-width button became a 600px table containing
a 552px link and 48px horizontal padding. With 40px section padding on either
side, this forced a 680px layout even in a narrow viewport. Public renderer
arguments remain compatible; container width is no longer used to turn button
percentages into fixed pixel widths.

`tests/MJML/buttons.test.cjs` runs through `npm run test` and verifies the real
compiler output for full, partial, fractional, clamped, outline and automatic
button widths. Seven HTML cases failed before the production fix. PHP tests
also check container independence and direct/grouped buttons in padded columns
and grids. The CLI fixtures share `tests/MJML/bootstrap.php` and contain only
synthetic content, never the original reported email or its addresses/links.

An additional opt-in layout regression uses Playwright (already brought in by
`@wordpress/scripts`) and a locally installed Chrome:

```shell
npm run test:browser
```

Set `PLAYWRIGHT_CHANNEL=chromium` to use an installed Playwright Chromium instead.
The browser run is separate so the default suite does not require a browser.
It checks actual document scroll width and button bounds at 240, 280, 320, 375,
479, 480, 600 and 680px, with and without head styles. A legacy-markup control
must overflow at 320px. Browser profiles are temporary and page network requests
are blocked. This validates the isolated button layout in Chromium, not every
possible newsletter or Outlook/Apple Mail rendering engine. After deploying,
save affected newsletters again to regenerate their stored HTML.

### Email contrast protection

The newsletter styling panel now offers **Automatically improve text contrast**,
enabled by default through the boolean `rrze_newsletter_contrast_protection`
metadata. During editor saves, the final MJML-generated HTML is inspected in an
offscreen, sandboxed iframe. Its temporary CSP blocks external resources and
scripts; the iframe is removed on success, error or timeout. No analysis script
or temporary CSP is included in the delivered email.

The safeguard resolves computed text colors and the nearest opaque background,
including enclosing tables, groups and button backgrounds. It preserves colors
meeting 4.5:1 and replaces failing colors with whichever of black/white provides
greater contrast. The calculation follows the [WCAG luminance formula and
unrounded threshold](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html).
The 4.5:1 threshold is deliberately used for all text, including headings.
Original colors are snapshotted before changes so correcting a parent cannot
break readable descendants on a different background. When correction is needed,
explicit text colors are emitted inline, including preserved descendant colors.
Saved Gutenberg block content, links, images and backgrounds are not rewritten.

Background images/gradients, partial transparency, filters, blending and
unsupported computed colors are reported for manual review rather than guessed.
Hidden text and SVG/logo content are not adjusted. This is not a complete
accessibility audit, image-text analysis or a dark-mode guarantee. Analysis uses
a 680px viewport; arbitrary viewport-dependent color rules and downstream dynamic
content inserted after HTML generation still require separate checks.

After saving, a notice reports adjusted and unassessed text elements and offers
a sandboxed **Preview generated email** modal. The ordinary Gutenberg canvas is
unchanged. Test sends, archive output and new queue entries use the same stored
HTML. Disabling protection and saving regenerates the original colors from the
unchanged blocks. Existing queued/delivered messages are not rewritten. If the
analysis fails, the middleware shows an error and does not store unchecked HTML
or forward that post update.

```shell
npm run test:contrast
npm run test:browser
```

Contrast tests cover reference ratios, threshold boundaries, solid/inherited
backgrounds, nested readable children, links, captions, buttons, uncertain
backgrounds, idempotence, Outlook comments and personalization placeholders.
The installed jsdom lacks complete computed CSS, so unit tests explicitly adapt
its inheritance, named-color and gradient boundaries. They are not a browser
layout engine. Save-middleware tests run the actual module with recorded API,
notice and preview boundaries. The opt-in native-browser test covers real CSS,
iframe isolation, resource blocking, script blocking, cleanup and serialization.

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
