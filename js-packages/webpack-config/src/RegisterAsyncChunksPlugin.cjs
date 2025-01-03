const path = require('path');
const extensionId = require('./extensionId.cjs');
const { Compilation } = require('webpack');
const ConcatenatedModule = require('webpack/lib/optimize/ConcatenatedModule');

class RegisterAsyncChunksPlugin {
  static registry = {};

  processUrlPath(urlPath) {
    if (path.sep == '\\') {
      // separator on windows is "\", this will cause escape issues when used in url path.
      return urlPath.replace(/\\/g, '/');
    }
    return urlPath;
  }

  apply(compiler) {
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
              if (module?.resource && module.resource.split(path.sep).includes('src') && module._source?._value.includes('webpackChunkName: ')) {
                modulesToCheck[chunk.id].push(module);
              }

              // A ConcatenatedModule.
              if (module?.modules) {
                module.modules.forEach((module) => {
                  if (module.resource && module.resource.split(path.sep).includes('src') && module._source?._value.includes('webpackChunkName: ')) {
                    modulesToCheck[chunk.id].push(module);
                  }
                });
              }
            }
          }

          for (const sourceChunkId in modulesToCheck) {
            for (const module of modulesToCheck[sourceChunkId]) {
              // If the module source has an async webpack chunk, add the chunk id to flarum.reg
              // at the end of the module source.

              const reg = [];

              // Each line that has a webpackChunkName comment.
              [...module._source._value.matchAll(/.*\/\* webpackChunkName: .* \*\/.*/gm)].forEach(([match]) => {
                [...match.matchAll(/(.*?) webpackChunkName: '([^']*)'.*? \*\/ '([^']+)'.*?/gm)].forEach(([match, _, urlPath, importPath]) => {
                  urlPath = this.processUrlPath(urlPath);

                  // Import path is relative to module.resource, so we need to resolve it
                  const importPathResolved = path.resolve(path.dirname(module.resource), importPath);
                  const thisComposerJson = require(path.resolve(process.cwd(), '../composer.json'));
                  const namespace = extensionId(thisComposerJson.name);

                  const chunkModules = (c) => Array.from(compilation.chunkGraph.getChunkModulesIterable(c));

                  const relevantChunk = chunks.find((chunk) =>
                    chunkModules(chunk)?.find(
                      (module) =>
                        module.resource?.split('.')[0] === importPathResolved || module.rootModule?.resource?.split('.')[0] === importPathResolved
                    )
                  );

                  if (!relevantChunk) {
                    console.error(`Could not find chunk for ${importPathResolved}`);
                    return match;
                  }

                  const relevantChunkModules = chunkModules(relevantChunk);
                  const mainModule = relevantChunkModules.filter((m) => {
                    return m.resource?.split('.')[0] === importPathResolved || m.rootModule?.resource?.split('.')[0] === importPathResolved;
                  })[0];
                  const otherRelevantChunkModules = relevantChunkModules.filter((m) => m !== mainModule);

                  if (mainModule instanceof ConcatenatedModule && mainModule.modules) {
                    otherRelevantChunkModules.push(...mainModule.modules);
                  }

                  if (!mainModule) {
                    return match;
                  }

                  const moduleId = compilation.chunkGraph.getModuleId(mainModule);
                  const registrableModulesUrlPaths = new Map();
                  registrableModulesUrlPaths.set(urlPath, [relevantChunk.id, moduleId, namespace, urlPath]);

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
                    if (!module.resource?.includes(`${path.sep}src${path.sep}`)) {
                      return;
                    }

                    // The path right after the src/ directory, without the extension.
                    const regPathSep = `\\${path.sep}`;
                    const urlPath = this.processUrlPath(module.resource.replace(new RegExp(`.*${regPathSep}src${regPathSep}([^.]+)\..+`), '$1'));

                    if (!registrableModulesUrlPaths.has(urlPath)) {
                      registrableModulesUrlPaths.set(urlPath, [relevantChunk.id, moduleId, namespace, urlPath]);
                    }
                  });

                  registrableModulesUrlPaths.forEach(([chunkId, moduleId, namespace, urlPath]) => {
                    chunkModuleMemory[sourceChunkId] = chunkModuleMemory[sourceChunkId] || [];

                    if (
                      !chunkModuleMemory[sourceChunkId].includes(urlPath) &&
                      !RegisterAsyncChunksPlugin.registry[`${sourceChunkId}:${chunkId}:${moduleId}:${namespace}`]?.includes(urlPath)
                    ) {
                      reg.push(`flarum.reg.addChunkModule('${chunkId}', '${moduleId}', '${namespace}', '${urlPath}');`);
                      chunkModuleMemory[sourceChunkId].push(urlPath);
                      RegisterAsyncChunksPlugin.registry[`${sourceChunkId}:${chunkId}:${moduleId}:${namespace}`] ||= [];
                      RegisterAsyncChunksPlugin.registry[`${sourceChunkId}:${chunkId}:${moduleId}:${namespace}`].push(urlPath);
                    }
                  });

                  return match;
                });
              });

              module._source._value += reg.join('\n');
            }
          }
        }
      );
    });
  }
}

module.exports = RegisterAsyncChunksPlugin;
