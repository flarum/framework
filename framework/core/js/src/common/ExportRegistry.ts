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
   * Add a function to run when object of id "id" is added (or overriden).
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
   */
  getChunk(chunkId: number | string): Chunk | null;

  /**
   * The chunk loader which overrides the default Webpack chunk loader.
   */
  loadChunk(original: Function, url: string, done: () => Promise<void>, key: number, chunkId: number | string): Promise<void>;

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
  chunks = new Map<string, Chunk>();
  chunkModules = new Map<string, Module>();
  private _revisions: any = null;

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
    const error = `No module found for ${namespace}:${id}`;

    // @ts-ignore
    if (!module && flarum.debug) {
      throw new Error(error);
    } else if (!module) {
      console.warn(error);
    }

    return module;
  }

  public checkModule(namespace: string, id: string): any | false {
    const exists = (this.moduleExports.has(namespace) && this.moduleExports.get(namespace)?.has(id)) || false;

    return exists ? this.get(namespace, id) : false;
  }

  addChunkModule(chunkId: number | string, moduleId: number | string, namespace: string, urlPath: string): void {
    if (!this.chunks.has(chunkId.toString())) {
      this.chunks.set(chunkId.toString(), {
        namespace,
        urlPath,
        modules: [urlPath],
      });
    } else {
      this.chunks.get(chunkId.toString())?.modules?.push(urlPath);
    }

    this.chunkModules.set(`${namespace}:${urlPath}`, {
      chunkId: chunkId.toString(),
      moduleId: moduleId.toString(),
    });
  }

  getChunk(chunkId: number | string): Chunk | null {
    const chunk = this.chunks.get(chunkId.toString()) ?? null;

    if (!chunk) {
      console.warn(`[Export Registry] No chunk by the ID ${chunkId} found.`);
      return null;
    }

    return chunk;
  }

  async loadChunk(original: Function, url: string, done: (...args: any) => Promise<void>, key: number, chunkId: number | string): Promise<void> {
    // @ts-ignore
    app.alerts.showLoading();

    return await original(
      this.chunkUrl(chunkId) || url,
      (...args: any) => {
        // @ts-ignore
        app.alerts.clearLoading();

        return done(...args);
      },
      key,
      chunkId
    );
  }

  chunkUrl(chunkId: number | string): string | null {
    const chunk = this.getChunk(chunkId.toString());

    if (!chunk) return null;

    this._revisions ??= JSON.parse(document.getElementById('flarum-rev-manifest')?.textContent ?? '{}');

    // @ts-ignore cannot import the app object here, so we use the global one.
    const path = `${app.forum.attribute<string>('jsChunksBaseUrl')}/${chunk.namespace}/${chunk.urlPath}.js`;

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
      throw new Error(`No chunk found for module ${namespace}:${id}`);
    }

    // @ts-ignore
    const wr = __webpack_require__;

    return await wr.e(module.chunkId).then(() => {
      // Needed to make sure the module is loaded.
      // Taken care of by webpack.
      wr.bind(wr, module.moduleId)();

      const moduleExport = this.get(namespace, id);

      // For consistent access to async modules.
      moduleExport.default = moduleExport.default || moduleExport;

      return moduleExport;
    });
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
