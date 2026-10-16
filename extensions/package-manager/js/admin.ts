/*
 * 🚨 The webpack entrypoint lives HERE, at the js root — not in src/.
 * flarum-webpack-config looks for js/admin.ts and js/forum.ts, and finding
 * neither it reports "No JS entrypoints could be found" and then compiles
 * successfully with nothing in it.
 *
 * `export *`, not a bare import: a bare import runs the module for its side
 * effects but exports nothing, and every extender registered inside it silently
 * no-ops.
 */
export * from './src/admin';
