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
    moduleExports: Map<string, Map<string, any>>;
    onLoads: Map<string, Map<string, Function[]>>;
    /** The first chunk registered under each id, as before ids were namespaced. */
    chunks: Map<string, Chunk>;
    chunkModules: Map<string, Module>;
    /** Every chunk, by `namespace:chunkId`. */
    private namespacedChunks;
    private _revisions;
    private _webpack_runtimes;
    add(namespace: string, id: string, object: any): void;
    onLoad(namespace: string, id: string, handler: (module: any) => void): void;
    get(namespace: string, id: string): any;
    checkModule(namespace: string, id: string): any | false;
    addChunkModule(chunkId: number | string, moduleId: number | string, namespace: string, urlPath: string): void;
    getChunk(chunkId: number | string, namespace?: string, url?: string): Chunk | null;
    /**
     * For bundles that don't say which build is asking. Webpack names the file
     * after the chunk, so where ids collide the URL usually tells them apart;
     * two builds with a chunk of the same name can't be, and the first wins.
     */
    private legacyChunk;
    loadChunk(original: Function, url: string, done: (...args: any) => Promise<void>, key: number, chunkId: number | string, namespace?: string): Promise<void>;
    chunkUrl(chunkId: number | string, namespace?: string, url?: string): string | null;
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
    private rebuiltChunkUrl;
    /**
     * The url path webpack encoded in a chunk's file name, relative to the
     * frontend it belongs to — the same value {@link Chunk.urlPath} holds for a
     * registered chunk.
     */
    private chunkUrlPath;
    /**
     * A chunk's url, carrying the revision recorded for it so a rebuild is not
     * served from cache.
     */
    private versionedChunkUrl;
    asyncModuleImport(path: string): Promise<any>;
    clear(): void;
    namespaceAndIdFromPath(path: string): [string, string];
}
export {};
