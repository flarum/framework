const path = require('path');
const extensionId = require('./extensionId.cjs');
const { Compilation } = require('webpack');
const ConcatenatedModule = require('webpack/lib/optimize/ConcatenatedModule');

class RegisterAsyncChunksPlugin {
  static registry = {};

  /**
   * @param {object} [options]
   * @param {string} [options.composerPath] where to read the extension's
   *   composer.json from, for the namespace. Defaults to the one beside the
   *   `js` directory the build runs in, which is where every extension keeps
   *   it; tests compile fixtures from elsewhere and say so explicitly.
   */
  constructor({ composerPath } = {}) {
    this.composerPath = composerPath ?? path.resolve(process.cwd(), '../composer.json');
  }

  /**
   * The `addChunkModule` calls for one module's async imports, in the order
   * flarum.reg needs them.
   *
   * Each import contributes its own registration — the chunk it loads, under
   * the module it is named after — and one for every other module that chunk
   * carries. A module can be carried by more than one chunk: when a lazily
   * imported component statically imports another that is lazily imported in
   * its own right, the second is in both chunks. flarum.reg resolves a chunk
   * id to the url path it was *first* registered with, and a module to the
   * chunk it was *last* registered under, so:
   *
   * - every own registration goes first, so a chunk is never named after a
   *   module it merely carries;
   * - a carried module that has its own registration in the same chunk is not
   *   registered as a passenger at all. Otherwise it is either loaded through
   *   the other chunk, or — registered as a passenger first — it blocks its own
   *   chunk's registration, leaving that chunk with no url.
   *
   * @param {Array<[string|number, string|number, string, string, boolean]>} candidates
   *   [chunkId, moduleId, namespace, urlPath, isOwn] for every import in the module
   * @param {Set<string>} ownUrlPaths url paths with their own registration
   *   anywhere in the chunk this module is in
   */
  registrations(candidates, ownUrlPaths, sourceChunkId, memory) {
    const own = candidates.filter((candidate) => candidate[4]);
    const passengers = candidates.filter((candidate) => !candidate[4] && !ownUrlPaths.has(candidate[3]));

    memory[sourceChunkId] ||= [];

    const reg = [];

    for (const [chunkId, moduleId, namespace, urlPath] of [...own, ...passengers]) {
      const registryKey = `${sourceChunkId}:${chunkId}:${moduleId}:${namespace}`;

      if (memory[sourceChunkId].includes(urlPath) || RegisterAsyncChunksPlugin.registry[registryKey]?.includes(urlPath)) {
        continue;
      }

      reg.push(`flarum.reg.addChunkModule('${chunkId}', '${moduleId}', '${namespace}', '${urlPath}');`);
      memory[sourceChunkId].push(urlPath);
      (RegisterAsyncChunksPlugin.registry[registryKey] ||= []).push(urlPath);
    }

    return reg;
  }

  processUrlPath(urlPath) {
    if (path.sep == '\\') {
      // separator on windows is "\", this will cause escape issues when used in url path.
      return urlPath.replace(/\\/g, '/');
    }
    return urlPath;
  }

  moduleUrlPath(module, sourceRoot) {
    const resource = module.rootModule?.resource ?? module.resource;

    if (!resource?.startsWith(sourceRoot)) return null;

    return this.processUrlPath(resource.slice(sourceRoot.length).replace(path.extname(resource), ''));
  }

  apply(compiler) {
    const sourceRoot = path.resolve(compiler.context, 'src') + path.sep;

    compiler.hooks.thisCompilation.tap('RegisterAsyncChunksPlugin', (compilation) => {
      let alreadyOptimized = false;

      compilation.hooks.unseal.tap('RegisterAsyncChunksPlugin', () => {
        alreadyOptimized = false;
        RegisterAsyncChunksPlugin.registry = {};
      });

      compilation.hooks.finishModules.tap('RegisterAsyncChunksPlugin', () => {
        alreadyOptimized = false;
        RegisterAsyncChunksPlugin.registry = {};
      });

      compilation.hooks.processAssets.tap(
        {
          name: 'RegisterAsyncChunksPlugin',
          stage: Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL,
        },
        () => {
          if (alreadyOptimized) return;
          alreadyOptimized = true;

          const chunks = Array.from(compilation.chunks);
          const chunkModuleMemory = {};
          const modulesToCheck = {};

          for (const chunk of chunks) {
            for (const module of compilation.chunkGraph.getChunkModulesIterable(chunk)) {
              modulesToCheck[chunk.id] = modulesToCheck[chunk.id] || [];

              // A normal module.
              if (this.moduleUrlPath(module, sourceRoot) && module._source?._value.includes('webpackChunkName: ')) {
                modulesToCheck[chunk.id].push(module);
              }

              // A ConcatenatedModule.
              if (module?.modules) {
                module.modules.forEach((module) => {
                  if (this.moduleUrlPath(module, sourceRoot) && module._source?._value.includes('webpackChunkName: ')) {
                    modulesToCheck[chunk.id].push(module);
                  }
                });
              }
            }
          }

          for (const sourceChunkId in modulesToCheck) {
            const pending = [];

            for (const module of modulesToCheck[sourceChunkId]) {
              // If the module source has an async webpack chunk, add the chunk id to flarum.reg
              // at the end of the module source.

              const candidates = [];
              pending.push([module, candidates]);

              // Each line that has a webpackChunkName comment.
              [...module._source._value.matchAll(/.*\/\* webpackChunkName: .* \*\/.*/gm)].forEach(([match]) => {
                [...match.matchAll(/(.*?) webpackChunkName: '([^']*)'.*? \*\/ '([^']+)'.*?/gm)].forEach(([match, _, urlPath, importPath]) => {
                  urlPath = this.processUrlPath(urlPath);

                  const dependency = module.blocks.flatMap((block) => block.dependencies).find((dependency) => dependency.request === importPath);
                  const importedModule = dependency && compilation.moduleGraph.getModule(dependency);

                  if (!importedModule) return match;

                  const thisComposerJson = require(this.composerPath);
                  const namespace = extensionId(thisComposerJson.name);

                  const chunkModules = (c) => Array.from(compilation.chunkGraph.getChunkModulesIterable(c));

                  // The chunk this import loads is the one webpack named after it:
                  // autoChunkNameLoader gave the import a `webpackChunkName` of
                  // exactly this url path. Looking instead for any chunk that
                  // contains the module finds the wrong one when another chunk
                  // carries it too (a lazily imported component that statically
                  // imports this one), so that is only the fallback.
                  const relevantChunk =
                    chunks.find((chunk) => chunk.name === urlPath) ?? chunks.find((chunk) => chunkModules(chunk).includes(importedModule));

                  if (!relevantChunk) {
                    console.error(`Could not find chunk for ${importPath}`);
                    return match;
                  }

                  const relevantChunkModules = chunkModules(relevantChunk);
                  const mainModule = relevantChunkModules.find((m) => m === importedModule || m.rootModule === importedModule);
                  const otherRelevantChunkModules = relevantChunkModules.filter((m) => m !== mainModule);

                  if (mainModule instanceof ConcatenatedModule && mainModule.modules) {
                    otherRelevantChunkModules.push(...mainModule.modules);
                  }

                  if (!mainModule) {
                    return match;
                  }

                  const moduleId = compilation.chunkGraph.getModuleId(mainModule);
                  const registrableModulesUrlPaths = new Map();
                  // Webpack may merge identical chunks and discard this import's original filename.
                  const chunkUrlPath = this.processUrlPath(relevantChunk.name ?? urlPath);
                  registrableModulesUrlPaths.set(chunkUrlPath, [relevantChunk.id, moduleId, namespace, chunkUrlPath, true]);

                  if (this.moduleUrlPath(mainModule, sourceRoot) && urlPath !== chunkUrlPath) {
                    registrableModulesUrlPaths.set(urlPath, [relevantChunk.id, moduleId, namespace, urlPath, true]);
                  }

                  const modules = [];

                  otherRelevantChunkModules.forEach((module) => {
                    if (module instanceof ConcatenatedModule && module.modules) {
                      modules.push(...module.modules);
                    } else {
                      modules.push(module);
                    }
                  });

                  // This is a chunk with many modules, we need to register all of them.
                  modules?.forEach((module) => {
                    const urlPath = this.moduleUrlPath(module, sourceRoot);

                    if (!urlPath) return;

                    if (!registrableModulesUrlPaths.has(urlPath)) {
                      registrableModulesUrlPaths.set(urlPath, [relevantChunk.id, moduleId, namespace, urlPath, false]);
                    }
                  });

                  candidates.push(...registrableModulesUrlPaths.values());

                  return match;
                });
              });
            }

            // The modules of one chunk run together when it loads, so a module
            // with its own registration anywhere in the chunk needs no passenger
            // copy in any of them.
            const ownUrlPaths = new Set(pending.flatMap(([, candidates]) => candidates.filter((c) => c[4]).map((c) => c[3])));

            for (const [module, candidates] of pending) {
              module._source._value += this.registrations(candidates, ownUrlPaths, sourceChunkId, chunkModuleMemory).join('\n');
            }
          }
        }
      );
    });
  }
}

module.exports = RegisterAsyncChunksPlugin;
