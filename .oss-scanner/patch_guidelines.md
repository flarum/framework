# How we would like patches made

## Size and scope of a fix
- Fix the root cause, as narrowly as that allows. If the same mistake is made in several places through a shared
  helper, fix the helper; otherwise change only what the issue needs. No refactoring, renaming or reformatting of
  unrelated code.
- Use the mechanism core already has for the job (policies and `whereVisibleTo` scopes for access, field
  `visible()` / `writable()` on API resources, the validator for input, TextFormatter's configuration for
  rendering) rather than ad hoc checks or escaping.
- Keep public APIs and extender signatures unchanged where possible: third-party extensions depend on them.

## Code style
- PHP follows the repository's StyleCI configuration (`.styleci.yml`, the `recommended` preset). Match the
  surrounding code.
- Comments only where the code cannot say why: a constraint, an invariant or a workaround. Usually one line.
  No comments that restate the code or describe the change.
- JavaScript and TypeScript: change `js/src` only. Never edit `js/dist` or `js/dist-typings`; a bot rebuilds
  them. Format with Prettier (`yarn format` in the package's `js/` directory).

## Tests
- Add a regression test that fails without the fix and passes with it, next to the existing tests for that
  feature in the package's `tests/integration` (or `tests/unit` for pure logic). Follow the existing tests:
  `prepareDatabase()` for fixtures, `$this->send($this->request(..., ['authenticatedAs' => $id]))` for requests.
- Test the denied cases as well as the allowed one: a guest, a member without the permission, and the user who
  should still succeed.
- Run the tests through the package's Composer scripts (`composer test:setup`, then
  `composer test:integration:filter -- <pattern>`), never by calling PHPUnit directly. When the fix touches SQL,
  run them on every driver with `flarum-db` (MariaDB, PostgreSQL and SQLite) and keep the fix driver-neutral:
  use the query builder, not raw SQL, unless each driver gets its own branch.

## Branch and commit message
- Against the `2.x` branch. Do not prepare 1.x backports; we decide those ourselves.
- One commit, conventional-commit style with the package as scope, for example
  `fix(tags): hide restricted tags from the include parameter`.
- No `Co-Authored-By` trailers or other attribution lines in the commit message.
