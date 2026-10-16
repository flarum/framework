Run the focused account-flagging suite from this extension's `js` directory:

```sh
node ../../../node_modules/jest/bin/jest.js --config tests/jest.config.cjs --runInBand
```

The suite uses the monorepo's Jest/Babel/JSDOM setup, real Flarum models, JSON:API
relationships, and extension registration. The test-local configuration emits
CommonJS consistently for legacy JSX `.js` and TypeScript files, without changing
production packaging.

It checks mixed account/post rendering and target routing, missing or ambiguous
targets, target grouping across colliding IDs, report creation, permission gates,
and successful/failed account dismissal. Network calls are mocked.

Mounted account-report and moderation modals also check target/reporter names
through the real Flarum translator and the core deleted-user fallback. These
checks catch locale placeholders that do not match the translator's user-model
parameter preprocessing.

Rendering checks isolate `HeaderList`'s scroll-layout lifecycle and the existing
`Post.contentPlain()` HTML-to-text utility because the test DOM does not implement
browser layout or `innerText`. They still mount the real moderation items and
links. They do not verify browser scrolling or HTML-to-text conversion.

Each render case resets Mithril's reentrancy sentinel: a thrown component view
otherwise makes subsequent test renders silently empty, hiding later failures.
