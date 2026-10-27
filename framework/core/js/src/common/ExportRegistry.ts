/**
 * @internal
 */
export interface IExportRegistry {
  moduleExports: Map<string, Map<string, any>>;
  onLoads: Map<string, Map<string, Function[]>>;

  /**
   * Add an instance to the registry.
   * Identified by a namespace (extension ID) and an ID (module path).
   */
  add(namespace: string, id: string, object: any): void;

  /**
   * Add a function to run when object of id "id" is added (or overridden).
   * If such an object is already registered, the handler will be applied immediately.
   */
  onLoad(namespace: string, id: string, handler: Function): void;

  /**
   * Retrieve a module from the registry by namespace and ID.
   */
  get(namespace: string, id: string): any;
}

/**
 * @internal
 */
export interface IChunkRegistry {
  chunks: Map<string, Chunk>;
  chunkModules: Map<string, Module>;

  /**
   * Check if a module has been loaded.
   * Return the module if so, false otherwise.
   */
  checkModule(namespace: string, id: string): any | false;

  /**
   * Register a module by the chunk ID it belongs to, the webpack module ID it belongs to,
   * the namespace (extension ID), and its path.
   */
  addChunkModule(chunkId: number | string, moduleId: number | string, namespace: string, urlPath: string): void;

  /**
   * Get a registered chunk. Each chunk has at least one module (the default one).
   *
   * Chunk ids are only unique within one build, so pass the namespace of the
   * build asking. Bundles built before flarum-webpack-config sent it can't, so
   * without it the chunk is matched by its file name in `url` where ids
   * collide, and is otherwise the first registered under the id.
   */
  getChunk(chunkId: number | string, namespace?: string, url?: string): Chunk | null;

  /**
   * The chunk loader which overrides the default Webpack chunk loader.
   */
  loadChunk(original: Function, url: string, done: () => Promise<void>, key: number, chunkId: number | string, namespace?: string): Promise<void>;

  /**
   * Responsible for loading external chunks.
   * Called automatically when an extension/package tries to async import a chunked module.
   */
  asyncModuleImport(path: string): Promise<any>;
}

type Chunk = {
  /**
   * The extension id of the chunk or 'core'.
   */
  namespace: string;
  /**
   * The relative URL path to the chunk.
   */
  urlPath: string;
  /**
   * An array of modules included in the chunk, by relative module path.
   */
  modules?: string[];
};

type Module = {
  /**
   * The chunk ID the module belongs to.
   */
  chunkId: string;
  /**
   * The module ID. Not unique, as most chunk modules are concatenated into one module.
   */
  moduleId: string;
};

export default class ExportRegistry implements IExportRegistry, IChunkRegistry {
  moduleExports = new Map<string, Map<string, any>>();
  onLoads = new Map<string, Map<string, Function[]>>();
  /** The first chunk registered under each id, as before ids were namespaced. */
  chunks = new Map<string, Chunk>();
  chunkModules = new Map<string, Module>();
  /** Every chunk, by `namespace:chunkId`. */
  private namespacedChunks = new Map<string, Chunk>();
  private _revisions: any = null;
  private _webpack_runtimes: any = {
    // @ts-ignore
    core: window.testing ? null : __webpack_require__,
  };

  add(namespace: string, id: string, object: any): void {
    this.moduleExports.set(namespace, this.moduleExports.get(namespace) || new Map());
    this.moduleExports.get(namespace)?.set(id, object);

    this.onLoads
      .get(namespace)
      ?.get(id)
      ?.forEach((handler) => handler(object));
  }

  onLoad(namespace: string, id: string, handler: (module: any) => void): void {
    if (this.moduleExports.has(namespace) && this.moduleExports.get(namespace)?.has(id)) {
      handler(this.moduleExports.get(namespace)?.get(id));
    } else {
      this.onLoads.set(namespace, this.onLoads.get(namespace) || new Map());
      this.onLoads.get(namespace)?.set(id, this.onLoads.get(namespace)?.get(id) || []);
      this.onLoads.get(namespace)?.get(id)?.push(handler);
    }
  }

  get(namespace: string, id: string): any {
    const module = this.moduleExports.get(namespace)?.get(id);
    const extensionEnabled = namespace in flarum.extensions || namespace === 'core';
    const error = `No module found for ${namespace}:${id}`;

    // Check if the module is registered in a chunk (will be loaded lazily)
    const isInChunk = this.chunkModules.has(`${namespace}:${id}`);

    // @ts-ignore
    if (!module && extensionEnabled && !isInChunk && flarum.debug) {
      throw new Error(error);
    } else if (!module && extensionEnabled && !isInChunk) {
      console.warn(error);
    }

    return module;
  }

  public checkModule(namespace: string, id: string): any | false {
    const exists = (this.moduleExports.has(namespace) && this.moduleExports.get(namespace)?.has(id)) || false;

    return exists ? this.get(namespace, id) : false;
  }

  addChunkModule(chunkId: number | string, moduleId: number | string, namespace: string, urlPath: string): void {
    const id = chunkId.toString();
    const key = `${namespace}:${id}`;
    const chunk = this.namespacedChunks.get(key);

    if (chunk) {
      chunk.modules?.push(urlPath);
    } else {
      const created: Chunk = { namespace, urlPath, modules: [urlPath] };

      this.namespacedChunks.set(key, created);

      if (!this.chunks.has(id)) this.chunks.set(id, created);
    }

    this.chunkModules.set(`${namespace}:${urlPath}`, {
      chunkId: chunkId.toString(),
      moduleId: moduleId.toString(),
    });
  }

  getChunk(chunkId: number | string, namespace?: string, url?: string): Chunk | null {
    const id = chunkId.toString();
    const chunk = (namespace ? this.namespacedChunks.get(`${namespace}:${id}`) : this.legacyChunk(id, url)) ?? null;

    if (!chunk) {
      console.warn(`[Export Registry] No chunk by the ID ${chunkId} found.`);
      return null;
    }

    return chunk;
  }

  /**
   * For bundles that don't say which build is asking. Webpack names the file
   * after the chunk, so where ids collide the URL usually tells them apart;
   * two builds with a chunk of the same name can't be, and the first wins.
   */
  private legacyChunk(id: string, url?: string): Chunk | undefined {
    if (url) {
      const file = url.split('?')[0];

      for (const [key, chunk] of this.namespacedChunks) {
        if (key.endsWith(`:${id}`) && (file === `${chunk.urlPath}.js` || file.endsWith(`/${chunk.urlPath}.js`))) {
          return chunk;
        }
      }
    }

    return this.chunks.get(id);
  }

  async loadChunk(
    original: Function,
    url: string,
    done: (...args: any) => Promise<void>,
    key: number,
    chunkId: number | string,
    namespace?: string
  ): Promise<void> {
    // @ts-ignore
    app.alerts.showLoading();

    const chunkUrl = this.chunkUrl(chunkId, namespace, url) ?? this.rebuiltChunkUrl(url, namespace) ?? url;

    const load = (): Promise<void> =>
      original(
        chunkUrl,
        (...args: any) => {
          const event: Event | undefined = args[0];

          // A chunk that failed to load because the browser is offline is
          // retried once connectivity is restored. Its import() promise stays
          // pending in the meantime, so every caller awaiting the chunk
          // recovers on its own — mirroring how `Application#request` defers
          // GET requests that fail while offline.
          if (event?.type === 'error' && navigator.onLine === false) {
            const retry = () => {
              window.removeEventListener('online', retry);
              load();
            };

            window.addEventListener('online', retry);

            return;
          }

          // @ts-ignore
          app.alerts.clearLoading();

          return done(...args);
        },
        key,
        chunkId
      );

    return await load();
  }

  chunkUrl(chunkId: number | string, namespace?: string, url?: string): string | null {
    const chunk = this.getChunk(chunkId.toString(), namespace, url);

    if (!chunk) return null;

    return this.versionedChunkUrl(chunk.namespace, chunk.urlPath);
  }

  /**
   * Where a chunk lives, for a chunk the registry does not know about.
   *
   * A chunk is registered by the module that imports it, so a lazy import
   * inside another lazy chunk is only registered once that outer chunk has
   * run — which is after the registry has been asked where the inner one is.
   *
   * Webpack's own url cannot stand in for it. Under automatic publicPath it
   * resolves a chunk against the directory the entry bundle was served from,
   * which is the assets root rather than `js/<namespace>/`. A forum serving
   * assets from a flat directory gets away with that; one serving them from
   * object storage, behind a CDN path, or from a subdirectory install does
   * not, and the request 404s (or 403s, where the bucket will not confirm a
   * key it is not allowed to list).
   *
   * Both missing pieces are available anyway: the namespace is passed in by
   * the runtime, and webpack names the file after the chunk's url path, so
   * the url it asked for carries that path. Nothing here depends on the
   * registry, only on the layout the asset compiler already writes.
   */
  private rebuiltChunkUrl(url?: string, namespace?: string): string | null {
    if (!url || !namespace) return null;

    // `…/forum/components/DeckPickerModal.js?v=1` → `forum/components/DeckPickerModal`.
    const urlPath = this.chunkUrlPath(url);

    if (!urlPath) return null;

    return this.versionedChunkUrl(namespace, urlPath);
  }

  /**
   * The url path webpack encoded in a chunk's file name, relative to the
   * frontend it belongs to — the same value {@link Chunk.urlPath} holds for a
   * registered chunk.
   */
  private chunkUrlPath(url: string): string | null {
    const file = url.split('?')[0].split('#')[0];
    const match = /(?:^|\/)((?:admin|common|forum)\/.+)\.js$/.exec(file);

    return match?.[1] ?? null;
  }

  /**
   * A chunk's url, carrying the revision recorded for it so a rebuild is not
   * served from cache.
   */
  private versionedChunkUrl(namespace: string, urlPath: string): string {
    this._revisions ??= JSON.parse(document.getElementById('flarum-rev-manifest')?.textContent ?? '{}');

    // @ts-ignore cannot import the app object here, so we use the global one.
    const path = `${app.forum.attribute<string>('jsChunksBaseUrl')}/${namespace}/${urlPath}.js`;

    // The paths in the revision are stored as (relative path from the assets path) + the path.
    // @ts-ignore
    const assetsPath = app.forum.attribute<string>('assetsBaseUrl');
    const key = path.replace(assetsPath, '').replace(/^\//, '');
    const revision = this._revisions[key];

    return revision ? `${path}?v=${revision}` : path;
  }

  async asyncModuleImport(path: string): Promise<any> {
    const [namespace, id] = this.namespaceAndIdFromPath(path);
    const module = this.chunkModules.get(`${namespace}:${id}`);

    if (!module) {
      // Not split into a chunk, but possibly in a bundle that has already
      // loaded: an extension decides for itself whether a module is lazy, and
      // one that imports it lazily can't know.
      const loaded = this.checkModule(namespace, id);

      if (loaded) {
        if (loaded.default !== undefined) return loaded;

        // Shaped as an imported chunk is below. Bundles register the default
        // export itself, which may be a primitive or frozen and can't take it.
        if (Object(loaded) === loaded && Object.isExtensible(loaded)) {
          loaded.default = loaded;

          return loaded;
        }

        return { default: loaded };
      }

      throw new Error(`No chunk found for module ${namespace}:${id}`);
    }

    // @ts-ignore
    const wr = this._webpack_runtimes[namespace] ?? __webpack_require__;

    return await wr
      .e(module.chunkId)
      .then(wr.bind(wr, module.moduleId))
      .then(() => {
        const m = this.get(namespace, id);

        m.default ??= m;

        return m;
      });
  }

  public clear(): void {
    this.moduleExports.clear();
    this.onLoads.clear();
    this.chunks.clear();
    this.chunkModules.clear();
    this.namespacedChunks.clear();
  }

  namespaceAndIdFromPath(path: string): [string, string] {
    // Either we get a path like `flarum/forum/components/LogInModal` or `ext:flarum/tags/forum/components/TagPage`.
    const matches = /^(?:ext:([^\/]+)\/(?:flarum-(?:ext-)?)?([^\/]+)|(flarum))(?:\/(.+))?$/.exec(path);

    const id = matches![4];
    let namespace;

    if (matches![1]) {
      namespace = `${matches![1]}-${matches![2]}`;
    } else {
      namespace = 'core';
    }

    return [namespace, id];
  }
}
