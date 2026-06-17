const path = require('path');
const extensionId = require('./extensionId.cjs');
const { Compilation } = require('webpack');
const ConcatenatedModule = require('webpack/lib/optimize/ConcatenatedModule');

/**
 * Records which registered modules are emitted into an initial (main) bundle chunk,
 * as opposed to living only in an async/code-split chunk.
 *
 * `flarum.reg`'s chunk-module map can't be used to tell these apart: a module may be
 * registered there yet still be present in the main bundle. This plugin uses the
 * authoritative build-time chunk graph instead, emitting
 * `flarum.reg.markInMainBundle('namespace:id')` for each registered default export
 * that webpack places in an initial chunk. Consumers (e.g. the dev-mode warning for
 * eagerly-shown modals) can then reliably ask whether a module ships up front.
 */
class MarkMainBundleModulesPlugin {
  apply(compiler) {
    compiler.hooks.thisCompilation.tap('MarkMainBundleModulesPlugin', (compilation) => {
      let done = false;

      compilation.hooks.processAssets.tap(
        {
          name: 'MarkMainBundleModulesPlugin',
          // Run after RegisterAsyncChunksPlugin (same stage) so our additions don't clash.
          stage: Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL,
        },
        () => {
          if (done) return;
          done = true;

          const thisComposerJson = require(path.resolve(process.cwd(), '../composer.json'));
          const namespace = extensionId(thisComposerJson.name);

          // The `src/`-relative path without extension — matches the id autoExportLoader
          // registers a default export under (e.g. `forum/components/RenameDiscussionModal`).
          const regPathSep = `\\${path.sep}`;
          const idFor = (resource) =>
            resource && resource.split(path.sep).includes('src')
              ? resource.replace(new RegExp(`.*${regPathSep}src${regPathSep}([^.]+)\\..+`), '$1').replace(/\\/g, '/')
              : null;

          // A module that has a default export — autoExportLoader appends a `flarum.reg.add`
          // for these, so they are the ones worth marking.
          const hasDefaultExport = (m) => /flarum\.reg\.add\('[^']+', '[^']+',/.test(m?._source?._value ?? '');

          const ids = new Set();

          for (const chunk of compilation.chunks) {
            if (!chunk.canBeInitial()) continue;

            for (const module of compilation.chunkGraph.getChunkModulesIterable(chunk)) {
              const candidates = module instanceof ConcatenatedModule && module.modules ? module.modules : [module];

              for (const m of candidates) {
                if (!hasDefaultExport(m)) continue;

                const id = idFor(m.resource);
                if (id) ids.add(`${namespace}:${id}`);
              }
            }
          }

          if (ids.size === 0) return;

          // Append the markers to an initial chunk's main asset so they run on boot.
          const initialChunk = Array.from(compilation.chunks).find((c) => c.canBeInitial());
          const file = initialChunk && Array.from(initialChunk.files)[0];
          if (!file) return;

          const marker = '\n' + Array.from(ids).map((key) => `flarum.reg.markInMainBundle(${JSON.stringify(key)});`).join('\n');

          compilation.updateAsset(file, (old) => new compiler.webpack.sources.ConcatSource(old, marker));
        }
      );
    });
  }
}

module.exports = MarkMainBundleModulesPlugin;
