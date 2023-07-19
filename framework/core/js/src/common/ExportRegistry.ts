/**
 * @internal
 */
export interface IExportRegistry {
  moduleExports: Map<string, Map<string, any>>;
  onLoads: Map<string, Map<string, Function[]>>;

  /**
   * Add an instance to the registry.
   */
  add(namespace: string, id: string, object: any): void;

  /**
   * Add a function to run when object of id "id" is added (or overriden).
   * If such an object is already registered, the handler will be applied immediately.
   */
  onLoad(namespace: string, id: string, handler: Function): void;

  /**
   * Retrieve an object of type `id` from the registry.
   */
  get(namespace: string, id: string): any;
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

export default class ExportRegistry implements IExportRegistry {
  moduleExports = new Map<string, Map<string, any>>();
  onLoads = new Map<string, Map<string, Function[]>>();
  chunks = new Map<string, Chunk>();
  moduleToChunk = new Map<string, string>();

  add(namespace: string, id: string, object: any): void {
    this.moduleExports.set(namespace, this.moduleExports.get(namespace) || new Map());
    this.moduleExports.get(namespace)?.set(id, object);

    this.onLoads
      .get(namespace)
      ?.get(id)
      ?.forEach((handler) => handler(object));
  }

  onLoad(namespace: string, id: string, handler: Function): void {
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

    if (!module) {
      console.warn(`No module found for ${namespace}:${id}`);
    }

    return module;
  }

  public check(namespace: string, id: string): any | false {
    const exists = (this.moduleExports.has(namespace) && this.moduleExports.get(namespace)?.has(id)) || false;

    return exists ? this.get(namespace, id) : false;
  }

  addChunkModule(chunkId: number | string, namespace: string, urlPath: string): void {
    if (!this.chunks.has(chunkId.toString())) {
      this.chunks.set(chunkId.toString(), {
        namespace,
        urlPath,
        modules: [urlPath],
      });
      this.moduleToChunk.set(`${namespace}:${urlPath}`, chunkId.toString());
    } else {
      this.chunks.get(chunkId.toString())?.modules?.push(urlPath);
      this.moduleToChunk.set(`${namespace}:${urlPath}`, chunkId.toString());
    }
  }

  getChunk(chunkId: number | string): Chunk | null {
    const chunk = this.chunks.get(chunkId.toString()) ?? null;

    if (!chunk) {
      console.warn(`[Export Registry] No chunk by the ID ${chunkId} found.`);
      return null;
    }

    return chunk;
  }
}
