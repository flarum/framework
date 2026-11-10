# Settings layout regression check

This check compiles the settings, form and admin-page LESS with less.js and checks geometry in Chromium at desktop and mobile widths. It uses an isolated HTML fixture, not the actual components or a signed-in forum. Jest's jsdom environment does not perform layout.

This catches the desktop column regression, but does not cover the full admin stylesheet, Flarum's production less.php compiler, or page-specific markup. A visual check of the signed-in admin page is still needed.

From the monorepo root, with `CHROME_BIN` pointing to Chromium:

```sh
layout_scratch=$(mktemp -d)
npm install --prefix "$layout_scratch" --no-audit --no-fund less@4 playwright-core@1
cp framework/core/js/tests/layout/settings-section.mjs "$layout_scratch/settings-section.mjs"
LAYOUT_CHECKOUT="$PWD" node --test "$layout_scratch/settings-section.mjs"
```

The browser dependencies stay outside the checkout. This check is manual and does not run in Jest or CI.
