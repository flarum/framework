const path = require("path");
const extensionId = require("./extensionId.cjs");
const {Compilation} = require("webpack");

class RegisterAsyncChunksPlugin {
  apply(compiler) {
    compiler.hooks.thisCompilation.tap("RegisterAsyncChunksPlugin", (compilation) => {
      let alreadyOptimized = false;
      compilation.hooks.unseal.tap("RegisterAsyncChunksPlugin", () => {
        alreadyOptimized = false;
      });

      compilation.hooks.processAssets.tap(
        {
          name: "RegisterAsyncChunksPlugin",
          stage: Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL,
        },
        () => {
          if (alreadyOptimized) return;
          alreadyOptimized = true;

          const chunks = Array.from(compilation.chunks);
          const chunkModuleMemory = [];

          const modulesToCheck = [];

          for (const chunk of chunks) {
            for (const module of compilation.chunkGraph.getChunkModulesIterable(chunk)) {
              // A normal module.
              if (module?.resource && module.resource.includes("/src/") && module._source?._value.includes("webpackChunkName: ")) {
                modulesToCheck.push(module);
              }

              // A ConcatenatedModule.
              if (module?.modules) {
                module.modules.forEach((module) => {
                  if (module.resource && module.resource.includes("/src/") && module._source?._value.includes("webpackChunkName: ")) {
                    modulesToCheck.push(module);
                  }
                });
              }
            }
          }

          for (const module of modulesToCheck) {
            // If the module source has an async webpack chunk, add the chunk id to flarum.reg
            // at the end of the module source.

            const reg = [];

            // Each line that has a webpackChunkName comment.
            [...module._source._value.matchAll(/.*\/\* webpackChunkName: .* \*\/.*/gm)].forEach(([match]) => {
              [...match.matchAll(/(.*?) webpackChunkName: '([^']*)'.*? \*\/ '([^']+)'.*?/gm)]
                .forEach(([match, _, urlPath, importPath]) => {
                  // Import path is relative to module.resource, so we need to resolve it
                  const importPathResolved = path.resolve(path.dirname(module.resource), importPath);
                  const thisComposerJson = require(path.resolve(process.cwd(), '../composer.json'));
                  const namespace = extensionId(thisComposerJson.name);

                  const chunkModules = (c) => Array.from(compilation.chunkGraph.getChunkModulesIterable(c));

                  const relevantChunk = chunks.find(
                    (chunk) => chunkModules(chunk)?.find(
                      (module) => module.resource?.split('.')[0] === importPathResolved || module.rootModule?.resource?.split('.')[0] === importPathResolved
                    )
                  );

                  if (! relevantChunk) {
                    console.error(`Could not find chunk for ${importPathResolved}`);
                    return match;
                  }

                  let concatenatedModule;
                  const registrableModulesUrlPaths = new Map();
                  registrableModulesUrlPaths.set(urlPath, [relevantChunk.id, namespace, urlPath]);

                  if ((concatenatedModule = chunkModules(relevantChunk)[0])?.rootModule) {
                    // This is a chunk with many modules, we need to register all of them.
                    concatenatedModule.modules?.forEach((module) => {
                      // The path right after the src/ directory, without the extension.
                      const urlPath = module.resource.replace(/.*\/src\/(.*)\..*/, '$1');

                      if (! registrableModulesUrlPaths.has(urlPath)) {
                        registrableModulesUrlPaths.set(urlPath, [relevantChunk.id, namespace, urlPath]);
                      }
                    });
                  }

                  registrableModulesUrlPaths.forEach(([chunkId, namespace, urlPath]) => {
                    if (! chunkModuleMemory.includes(urlPath)) {
                      reg.push(`flarum.reg.addChunkModule('${chunkId}', '${namespace}', '${urlPath}');`);
                      chunkModuleMemory.push(urlPath);
                    }
                  });

                  return match;
                });
            });

            module._source._value += reg.join('\n');
          }
        }
      );
    });
  }
}

module.exports = RegisterAsyncChunksPlugin;
