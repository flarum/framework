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
    moduleExports: Map<string, Map<string, any>>;
    onLoads: Map<string, Map<string, Function[]>>;
    chunks: Map<string, Chunk>;
    chunkModules: Map<string, Module>;
    private _revisions;
    add(namespace: string, id: string, object: any): void;
    onLoad(namespace: string, id: string, handler: (module: any) => void): void;
    get(namespace: string, id: string): any;
    checkModule(namespace: string, id: string): any | false;
    addChunkModule(chunkId: number | string, moduleId: number | string, namespace: string, urlPath: string): void;
    getChunk(chunkId: number | string): Chunk | null;
    loadChunk(original: Function, url: string, done: (...args: any) => Promise<void>, key: number, chunkId: number | string): Promise<void>;
    chunkUrl(chunkId: number | string): string | null;
    asyncModuleImport(path: string): Promise<any>;
    clear(): void;
    namespaceAndIdFromPath(path: string): [string, string];
}
export {};
