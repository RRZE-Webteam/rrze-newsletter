# Email social icons

`services.json` is the renderer's service registry. Every service has 128×128
black and white PNGs. They are bundled locally, not fetched from a third-party
icon service at send time. Existing thirteen service PNGs are retained.

The 36 new vector sources in `sources.json` were imported from WordPress
7.1.1-RC1 (`wp-includes/blocks/social-link.php`); their default colors come from
`wp-includes/blocks/social-links/style.css`. WordPress contributors are the source
authors. Source project: https://github.com/WordPress/wordpress-develop .
These derived assets are distributed under GPL-2.0-or-later, as is WordPress;
see https://github.com/WordPress/wordpress-develop/blob/trunk/license.txt .
Service names and logos remain trademarks of their respective owners.

Development commands (no live WordPress bootstrap or network requests):

```sh
npm run build:social-icons
npm run check:social-icons
npm run check:social-icons -- /path/to/wordpress
node scripts/social-icons.cjs import /path/to/wordpress
```

The last command imports newly added core services and rebuilds vector-derived
PNGs. It preserves existing service metadata and legacy assets. Review upstream
source/license changes and the resulting images before committing. The explicit
WordPress check fails if core has gained a service we do not yet support; run it
when updating WordPress. Normal tests do not depend on a local WordPress checkout.

Unknown/custom services retain their URL with the generic link icon and a visible,
escaped label. Empty links, unrelated blocks and unsafe URL schemes are skipped.
